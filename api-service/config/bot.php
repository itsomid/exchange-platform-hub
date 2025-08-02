<?php

return [
    'auth' => [
        'jwt_key' => env('BOT_AUTH_JWT_KEY', 'bitexroom_bot'),
        'secret_key' => env('BOT_AUTH_TOKEN_SECRET_KEY', 'Bot#2025'),
        'expiry_days' => env('BOT_AUTH_TOKEN_EXPIRY_DAYS', 30),
    ],
];
