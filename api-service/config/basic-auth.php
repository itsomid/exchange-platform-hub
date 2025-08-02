<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Basic Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is used for protecting routes with basic authentication.
    | Uses a single shared authentication for all protected areas.
    |
    */

    'default' => [
        'username' => env('BASIC_AUTH_USERNAME', 'admin'),
        'password' => env('BASIC_AUTH_PASSWORD', 'secret123'),
        'realm' => 'Protected Area',
        'lifetime' => env('BASIC_AUTH_LIFETIME', 86400), // 24 hours in seconds
    ],
];