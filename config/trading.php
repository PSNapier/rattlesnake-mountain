<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trade Offer Rate Limit
    |--------------------------------------------------------------------------
    |
    | Caps how many offers one player can create per decay window. Offers are
    | cheap to create and cost the sender nothing until accepted, so this exists
    | to stop inbox flooding rather than to price the action.
    |
    */

    'rate_limit' => [
        'max_attempts' => 30,
        'decay_seconds' => 3600,
    ],
];
