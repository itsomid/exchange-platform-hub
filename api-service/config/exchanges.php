<?php

return [
    'coinex' => [
        'base_url_v2' => env('EXCHANGES_COINEX_BASE_URL_V2', 'https://api.coinex.com'),
        'access_id' => env('EXCHANGES_COINEX_ACCESS_ID', '2F3E3042E7D74E76BE226245846696C2'),
        'secret_key' => env('EXCHANGES_COINEX_SECRET_KEY', '775F97CA208AF4329FB1614645319C9EC70F15D833FEC0BF'),
        'fee_currency' => 'CET',
    ],
    'mexc' => [
        'base_url' => env('EXCHANGES_MEXC_BASE_URL', 'https://api.mexc.com'),
        'api_key' => env('EXCHANGES_MEXC_API_KEY', ''),
        'secret_key' => env('EXCHANGES_MEXC_SECRET_KEY', ''),
        'fee_currency' => 'USDT',
    ],
];
