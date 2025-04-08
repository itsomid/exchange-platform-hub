<?php
return [
    'coinex' => [
        'base_url_v2' => env('EXCHANGES_COINEX_BASE_URL_V2', 'https://api.coinex.com'),
        'access_id' => env('EXCHANGES_COINEX_ACCESS_ID', ''),
        'secret_key' => env('EXCHANGES_COINEX_SECRET_KEY', ''),
    ]
];
