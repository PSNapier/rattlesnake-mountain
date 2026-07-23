<?php

namespace App\Services\Genetics;

use App\Data\BreedingGeneticsOption;
use App\Data\BreedingGeneticsRequest;
use App\Data\BreedingGeneticsResult;
use App\Services\Contracts\BreedingGeneticsProvider;
use RuntimeException;

class LocalPunnettGeneticsProvider implements BreedingGeneticsProvider
{
    public function __construct(private BreedingGeneticsParser $parser) {}

    public function name(): string
    {
        return 'local';
    }

    public function roll(BreedingGeneticsRequest $request): BreedingGeneticsResult
    {
        $possible = $this->parser->possibleOffspringGenos($request->sireGeno, $request->damGeno);
        if ($possible === []) {
            throw new RuntimeException('No valid offspring genotypes for the given parents.');
        }

        $count = (int) config('breeding.result_option_count', 2);
        $phenotype = (string) config('breeding.phenotype_placeholder', 'Phenotype: [feature coming soon!]');
        $options = [];

        // Deterministic shuffle keyed by idempotency key so retries are stable.
        $pool = $possible;
        $seed = crc32($request->idempotencyKey);
        usort($pool, function (string $a, string $b) use ($seed): int {
            return ($seed ^ crc32($a)) <=> ($seed ^ crc32($b));
        });

        for ($i = 0; $i < $count; $i++) {
            $geno = $pool[$i % count($pool)];
            $options[] = new BreedingGeneticsOption(
                geno: $this->parser->normalize($geno),
                phenotypeLabel: $phenotype,
            );
        }

        // Prefer distinct options when enough unique genotypes exist.
        if (count($pool) >= $count) {
            $options = [];
            for ($i = 0; $i < $count; $i++) {
                $options[] = new BreedingGeneticsOption(
                    geno: $this->parser->normalize($pool[$i]),
                    phenotypeLabel: $phenotype,
                );
            }
        }

        return new BreedingGeneticsResult(
            options: $options,
            provider: $this->name(),
            providerRequestId: $request->idempotencyKey,
            metadata: [
                'possible_count' => count($possible),
                'engine' => 'punnett_local_v1',
            ],
        );
    }
}
