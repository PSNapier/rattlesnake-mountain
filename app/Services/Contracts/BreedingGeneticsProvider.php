<?php

namespace App\Services\Contracts;

use App\Data\BreedingGeneticsRequest;
use App\Data\BreedingGeneticsResult;

interface BreedingGeneticsProvider
{
    public function name(): string;

    /**
     * Produce exactly config(breeding.result_option_count) genotype options.
     *
     * Implementations must be safe to retry with the same idempotency key.
     *
     * @throws \RuntimeException on timeout, malformed remote payload, or unsupported genotypes
     */
    public function roll(BreedingGeneticsRequest $request): BreedingGeneticsResult;
}
