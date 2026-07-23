<?php

return [
    'slots_per_horse' => 10,
    'minimum_age_months' => 24,
    'result_option_count' => 2,
    'phenotype_placeholder' => 'Phenotype: [feature coming soon!]',
    'request_rate_limit' => [
        'max_attempts' => 10,
        'decay_seconds' => 3600,
    ],
    'transfer_rate_limit' => [
        'max_attempts' => 20,
        'decay_seconds' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Genetics provider
    |--------------------------------------------------------------------------
    |
    | local  — in-app Punnett engine (MVP default)
    | fake_external — contract test double
    | external — reserved for a future HTTP genetics API
    |
    */
    'genetics_provider' => env('BREEDING_GENETICS_PROVIDER', 'local'),

    'external' => [
        'base_url' => env('BREEDING_GENETICS_API_URL'),
        'token' => env('BREEDING_GENETICS_API_TOKEN'),
        'timeout_seconds' => (int) env('BREEDING_GENETICS_API_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported loci
    |--------------------------------------------------------------------------
    |
    | required loci must appear in every breedable genotype.
    | optional loci may be omitted (treated as wild-type nn).
    | Flaxen and Pangare intentionally unsupported (no genotype tokens).
    |
    */
    'loci' => [
        'E' => [
            'required' => true,
            'alleles' => ['E', 'e'],
            'display_order' => 1,
        ],
        'A' => [
            'required' => true,
            'alleles' => ['A+', 'A', 'a'],
            'display_order' => 2,
        ],
        'Cr' => [
            'required' => false,
            'alleles' => ['Cr', 'n'],
            'display_order' => 3,
        ],
        'Prl' => [
            'required' => false,
            'alleles' => ['Prl', 'n'],
            'display_order' => 4,
        ],
        'D' => [
            'required' => false,
            'alleles' => ['D', 'n'],
            'display_order' => 5,
        ],
        'Z' => [
            'required' => false,
            'alleles' => ['Z', 'n'],
            'display_order' => 6,
        ],
        'Ch' => [
            'required' => false,
            'alleles' => ['Ch', 'n'],
            'display_order' => 7,
        ],
        'G' => [
            'required' => false,
            'alleles' => ['G', 'n'],
            'display_order' => 8,
        ],
        'Rb' => [
            'required' => false,
            'alleles' => ['Rb', 'n'],
            'display_order' => 9,
        ],
        'Spl' => [
            'required' => false,
            'alleles' => ['Spl', 'n'],
            'display_order' => 10,
        ],
        'T' => [
            'required' => false,
            'alleles' => ['T', 'n'],
            'display_order' => 11,
        ],
        'O' => [
            'required' => false,
            'alleles' => ['O', 'n'],
            'display_order' => 12,
        ],
    ],
];
