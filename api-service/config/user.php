<?php

return [
    'referral-code' => [
        'usage-limit' => env('REFERRAL_CODE_USAGE_LIMIT', 30),
        'max-fee' => env('REFERRAL_CODE_MAX_FEE', 30),
    ],
];
