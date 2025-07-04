<?php

return [
    'file_upload_url_4' => env('BITEX_FILE_UPLOAD_URL_4', ''),
    'api_service' => [
        'base_url' => env('BITEX_API_SERVICE_BASE_URL', 'http://nginx-api'),
    ],
    'contracts' => [
        'base_url' => env('CONTRACTS_BASE_URL', 'http://127.0.0.1:8000'),
    ],
    'user_id' => env('BITEXROOM_USER_ID', 1),
    'scale_precision' => 8,
];
