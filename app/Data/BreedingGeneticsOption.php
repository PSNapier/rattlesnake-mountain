<?php

namespace App\Data;

readonly class BreedingGeneticsOption
{
    public function __construct(
        public string $geno,
        public string $phenotypeLabel = 'Phenotype: [feature coming soon!]',
    ) {}

    /**
     * @return array{geno: string, phenotype: string}
     */
    public function toArray(): array
    {
        return [
            'geno' => $this->geno,
            'phenotype' => $this->phenotypeLabel,
        ];
    }
}
