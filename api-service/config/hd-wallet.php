<?php

return [
    // Old HD Wallet Settings
    'base_url' => env('HD_WALLET_BASE_URL', 'http://209.145.49.107:8888'),
    
    // New HD Wallet Settings
    'new_base_url' => env('HD_WALLET_NEW_BASE_URL', 'http://localhost:3000'),
    'api_key' => env('HD_WALLET_NEW_API_KEY', 'a8F3kLm92QxT7vBn4ZpR6sWd1YhJ9uKc5MxN2tGq8LrV0bHy7DpE3fSa6ZxC1'),
    
    // Switch between old and new system
    // Set to 'old' or 'new'
    'active_system' => env('HD_WALLET_ACTIVE_SYSTEM', 'old'),
];
