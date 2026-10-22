<?php

return [
    'auth' => [
        'jwt_key' => env('ACCOUNTING_AUTH_JWT_KEY', ''),
        'secret_key' => env('ACCOUNTING_AUTH_TOKEN_SECRET_KEY', ''),
        'expiry_days' => env('ACCOUNTING_AUTH_TOKEN_EXPIRY_DAYS', 30),
    ],
];
