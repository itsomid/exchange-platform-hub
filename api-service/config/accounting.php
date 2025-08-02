<?php

return [
    'auth' => [
        'jwt_key' => env('ACCOUNTING_AUTH_JWT_KEY', 'bitexrom'),
        'secret_key' => env('ACCOUNTING_AUTH_TOKEN_SECRET_KEY', 'Bitex#2025'),
        'expiry_days' => env('ACCOUNTING_AUTH_TOKEN_EXPIRY_DAYS', 30),
    ],
];
