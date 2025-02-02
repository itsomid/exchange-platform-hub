<?php

return [
    'scale_precision' => env('BITEXROOM_SCALE_PRECISION', 8),
    'deposit_watching_per_minutes' => env('BITEXROOM_DEPOSIT_WATCHING_PER_MINUTES', 60 * 8), //Default is 8H
    'bitexroom_user_id' => env('BITEXROOM_USER_ID', 1),
    'wallet_refresh' => [
        'minutes' => 2,
        'max_attempts' => 1,
    ],
    'withdrawal' => [
        'check_wallet_attempts' => [
            'minutes' => 20,
            'max_attempts' => 20,
        ],
    ],
];
