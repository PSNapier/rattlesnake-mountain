<?php

namespace App\Services\Genetics;

use InvalidArgumentException;

class BreedingGeneticsParser
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function parse(string $geno): array
    {
        $geno = trim(preg_replace('/\s+/', ' ', $geno) ?? '');
        if ($geno === '') {
            throw new InvalidArgumentException('Genotype cannot be empty.');
        }

        $tokens = explode(' ', $geno);
        $parsed = [];

        foreach ($tokens as $token) {
            [$locus, $alleles] = $this->parseToken($token);
            if (isset($parsed[$locus])) {
                throw new InvalidArgumentException("Duplicate locus \"{$locus}\" in genotype.");
            }
            $parsed[$locus] = $alleles;
        }

        foreach ($this->loci() as $locus => $config) {
            if (($config['required'] ?? false) && ! isset($parsed[$locus])) {
                throw new InvalidArgumentException("Missing required locus \"{$locus}\".");
            }

            if (! isset($parsed[$locus])) {
                $parsed[$locus] = ['n', 'n'];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $allelesByLocus
     */
    public function format(array $allelesByLocus): string
    {
        $tokens = [];

        foreach ($this->orderedLocusKeys() as $locus) {
            $alleles = $allelesByLocus[$locus] ?? ['n', 'n'];
            $config = $this->loci()[$locus] ?? null;
            if ($config === null) {
                throw new InvalidArgumentException("Unsupported locus \"{$locus}\".");
            }

            $isWildType = $alleles === ['n', 'n'];
            if (! ($config['required'] ?? false) && $isWildType) {
                continue;
            }

            $tokens[] = $this->formatToken($locus, $alleles);
        }

        return implode(' ', $tokens);
    }

    public function normalize(string $geno): string
    {
        return $this->format($this->parse($geno));
    }

    public function isValid(string $geno): bool
    {
        try {
            $this->parse($geno);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @return list<array<string, array{0: string, 1: string}>>
     */
    public function punnettCombinations(string $sireGeno, string $damGeno): array
    {
        $sire = $this->parse($sireGeno);
        $dam = $this->parse($damGeno);
        $loci = $this->orderedLocusKeys();

        $combinations = [[]];

        foreach ($loci as $locus) {
            $sireAlleles = $sire[$locus] ?? ['n', 'n'];
            $damAlleles = $dam[$locus] ?? ['n', 'n'];
            $offspringAlleles = [];

            foreach ($sireAlleles as $sireAllele) {
                foreach ($damAlleles as $damAllele) {
                    $pair = $this->orderAllelePair($locus, $sireAllele, $damAllele);
                    $key = $pair[0].'|'.$pair[1];
                    $offspringAlleles[$key] = $pair;
                }
            }

            $next = [];
            foreach ($combinations as $partial) {
                foreach ($offspringAlleles as $pair) {
                    $partial[$locus] = $pair;
                    $next[] = $partial;
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @return list<string>
     */
    public function possibleOffspringGenos(string $sireGeno, string $damGeno): array
    {
        $formatted = [];
        foreach ($this->punnettCombinations($sireGeno, $damGeno) as $combo) {
            $formatted[$this->format($combo)] = true;
        }

        return array_keys($formatted);
    }

    /**
     * @return array{0: string, array{0: string, 1: string}}
     */
    private function parseToken(string $token): array
    {
        foreach ($this->orderedLocusKeys() as $locus) {
            $alleles = $this->matchLocusToken($locus, $token);
            if ($alleles !== null) {
                return [$locus, $alleles];
            }
        }

        throw new InvalidArgumentException("Unsupported or malformed genotype token \"{$token}\".");
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function matchLocusToken(string $locus, string $token): ?array
    {
        $allowed = $this->loci()[$locus]['alleles'] ?? [];
        $nonWild = array_values(array_filter($allowed, fn (string $allele): bool => $allele !== 'n'));

        // Required diploid forms: EE, Ee, ee / A+A+, A+A, A+a, AA, Aa, aa
        if (($this->loci()[$locus]['required'] ?? false) === true) {
            foreach ($allowed as $first) {
                foreach ($allowed as $second) {
                    $candidate = $first.$second;
                    if ($candidate === $token) {
                        return $this->orderAllelePair($locus, $first, $second);
                    }
                }
            }

            return null;
        }

        // Optional modifiers: nn, nCr / Crn, CrCr (dominant allele token from config)
        if (count($nonWild) !== 1) {
            return null;
        }

        $dominant = $nonWild[0];
        $variants = [
            'nn' => ['n', 'n'],
            'n'.$dominant => ['n', $dominant],
            $dominant.'n' => ['n', $dominant],
            $dominant.$dominant => [$dominant, $dominant],
        ];

        foreach ($variants as $form => $alleles) {
            if ($form === $token) {
                return $this->orderAllelePair($locus, $alleles[0], $alleles[1]);
            }
        }

        return null;
    }

    /**
     * @param  array{0: string, 1: string}  $alleles
     */
    private function formatToken(string $locus, array $alleles): string
    {
        if (($this->loci()[$locus]['required'] ?? false) === true) {
            return $alleles[0].$alleles[1];
        }

        if ($alleles[0] === 'n' && $alleles[1] === 'n') {
            return 'nn';
        }

        if ($alleles[0] === $alleles[1]) {
            return $alleles[0].$alleles[1];
        }

        $dominant = $alleles[0] === 'n' ? $alleles[1] : $alleles[0];

        return 'n'.$dominant;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function orderAllelePair(string $locus, string $first, string $second): array
    {
        $priority = $this->allelePriority($locus);
        if (! array_key_exists($first, $priority) || ! array_key_exists($second, $priority)) {
            throw new InvalidArgumentException("Invalid allele for locus \"{$locus}\".");
        }

        // Optional heterozygous modifiers always store as [n, Dominant] for nCr-style tokens.
        if (($this->loci()[$locus]['required'] ?? false) === false && $first !== $second) {
            return $first === 'n' ? [$first, $second] : [$second, $first];
        }

        $firstRank = $priority[$first];
        $secondRank = $priority[$second];

        if ($firstRank <= $secondRank) {
            return [$first, $second];
        }

        return [$second, $first];
    }

    /**
     * @return array<string, int>
     */
    private function allelePriority(string $locus): array
    {
        $alleles = $this->loci()[$locus]['alleles'] ?? [];
        $priority = [];
        foreach (array_values($alleles) as $index => $allele) {
            $priority[$allele] = $index;
        }

        return $priority;
    }

    /**
     * @return list<string>
     */
    private function orderedLocusKeys(): array
    {
        $loci = $this->loci();
        uksort($loci, function (string $a, string $b) use ($loci): int {
            return ($loci[$a]['display_order'] ?? 99) <=> ($loci[$b]['display_order'] ?? 99);
        });

        return array_keys($loci);
    }

    /**
     * @return array<string, array{required: bool, alleles: list<string>, display_order?: int}>
     */
    private function loci(): array
    {
        /** @var array<string, array{required: bool, alleles: list<string>, display_order?: int}> $loci */
        $loci = config('breeding.loci', []);

        return $loci;
    }
}
