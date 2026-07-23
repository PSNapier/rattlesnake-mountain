<?php

namespace App\Data;

readonly class BreedingGeneticsResult
{
    /**
     * @param  list<BreedingGeneticsOption>  $options
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $options,
        public string $provider,
        public ?string $providerRequestId = null,
        public array $metadata = [],
    ) {}

    /**
     * @return list<array{geno: string, phenotype: string}>
     */
    public function optionsToArray(): array
    {
        return array_map(
            fn (BreedingGeneticsOption $option): array => $option->toArray(),
            $this->options,
        );
    }
}
