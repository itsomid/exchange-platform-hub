<?php

return [
    'base_url' => env('HD_WALLET_BASE_URL', 'http://localhost:8888'),
    'new_base_url' => env('HD_WALLET_NEW_BASE_URL', 'http://localhost:3000'),
    'new_api_key' => env('HD_WALLET_NEW_API_KEY', ''),
    'active_system' => env('HD_WALLET_ACTIVE_SYSTEM', 'old'),
    'wallet_id' => env('HD_WALLET_WALLET_ID', 'main'),
];
