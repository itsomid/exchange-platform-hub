<?php
return [
    'coinex' => [
        'base_url_v2' => env('EXCHANGES_COINEX_BASE_URL_V2', 'https://api.coinex.com'),
        'access_id' => env('EXCHANGES_COINEX_ACCESS_ID', ''),
        'secret_key' => env('EXCHANGES_COINEX_SECRET_KEY', ''),
        'fee_currency' => 'CET',
    ],
    'mexc' => [
        'base_url' => env('EXCHANGES_MEXC_BASE_URL', 'https://api.mexc.com'),
        'api_key' => env('EXCHANGES_MEXC_API_KEY', ''),
        'secret_key' => env('EXCHANGES_MEXC_SECRET_KEY', ''),
        'fee_currency' => 'USDT',
        'market_fee_rate' => 0.001, // MEXC market order fee rate (0.1%)
    ],
];
