<?php

/**
 * Redeemable voucher → choice map.
 *
 * Each voucher item name maps to either:
 * - choices: slug => item name (for short UI keys)
 * - items: list of item names (choice is the item name itself)
 * - source: shop_catalog (herb names resolved at runtime)
 */
return [
    'Cream/Pearl Stone Voucher' => [
        'choices' => [
            'cream' => 'Cream Stone',
            'pearl' => 'Pearl Stone',
        ],
    ],

    'Stone Voucher' => [
        // Client sign-off pending: same pool as welcome-package random stones.
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

    'Herb Voucher' => [
        'source' => 'shop_catalog',
    ],

    'Feather Voucher' => [
        // Client sign-off pending: placeholder rarity tiers until PvP specs land.
        'items' => [
            'Common Feather',
            'Rare Feather',
            'Legendary Feather',
        ],
    ],
];
