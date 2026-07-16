<?php

/**
 * Recruit-a-Friend bonuses granted when the recruit verifies email.
 *
 * Deferred until activities ([015]): +10 Scorpions and +1 stat per submission
 * for 3 months after referrals.granted_at.
 *
 * Voucher pools pending client sign-off (same caveat as welcome-package).
 */
return [
    'each' => [
        'Scorpion' => 100,
        'Stone Voucher' => 2,
        'Herb Voucher' => 2,
        'Feather Voucher' => 2,
    ],

    /*
     * Search endpoint for registration referrer picker.
     */
    'search' => [
        'min_length' => 2,
        'limit' => 10,
        'throttle' => '10,1',
    ],
];
