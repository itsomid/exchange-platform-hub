<?php

return [
    'api_service' => [
        'base_url' => env('BITEX_API_SERVICE_BASE_URL', 'http://nginx-api'),
    ],
    'contracts' => [
        'base_url' => env('CONTRACTS_FILE_BASE_URL', 'http://127.0.0.1:8001'),
    ],
    'user_id' => env('BITEXROOM_USER_ID', 1),
    'scale_precision' => 8,
];
