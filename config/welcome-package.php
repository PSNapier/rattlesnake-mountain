<?php

/**
 * Welcome package granted on registration.
 *
 * Stone and feather item names are derived pending client sign-off.
 * Cream/Pearl choice uses a voucher redeemed via inventory UI.
 */
return [
    'fixed' => [
        'Scorpion' => 500,
        'White-Modifier Stone' => 1,
        'Cream/Pearl Stone Voucher' => 1,
    ],

    'random_pools' => [
        'stones' => [
            'count' => 2,
            // Client sign-off pending: rare-gene stones derived from genetics modifiers.
            'items' => [
                'Cream Stone',
                'Pearl Stone',
                'Champagne Stone',
                'Grey Stone',
                'Silver Stone',
                'Splash Stone',
                'Tobiano Stone',
                'Overo Stone',
                'Rabicano Stone',
            ],
        ],
        'herbs' => [
            'count' => 2,
            // Resolved at runtime from database/data/shop_catalog.json
            'source' => 'shop_catalog',
        ],
        'feathers' => [
            'count' => 2,
            // Client sign-off pending: placeholder rarity tiers until PvP specs land.
            'items' => [
                'Common Feather',
                'Rare Feather',
                'Legendary Feather',
            ],
        ],
    ],

    'voucher' => [
        'item' => 'Cream/Pearl Stone Voucher',
        'choices' => [
            'cream' => 'Cream Stone',
            'pearl' => 'Pearl Stone',
        ],
    ],
];
