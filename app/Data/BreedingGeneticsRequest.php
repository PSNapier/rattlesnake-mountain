<?php

namespace App\Data;

readonly class BreedingGeneticsRequest
{
    public function __construct(
        public string $sireGeno,
        public string $damGeno,
        public string $idempotencyKey,
    ) {}
}
