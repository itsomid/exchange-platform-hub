<?php

return [
    'base_url' => env('HD_WALLET_BASE_URL', 'http://localhost:8888'),
    'new_base_url' => env('HD_WALLET_NEW_BASE_URL', 'http://localhost:3000'),
    'api_key' => env('HD_WALLET_API_KEY', 'api_key_placeholder'),
    'active_system' => env('HD_WALLET_ACTIVE_SYSTEM', 'old'),
];
