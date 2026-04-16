<?php

return [
    'api_key' => env('CRYPTOAPIS_API_KEY','50ca1691530dbf9396c36ca09345ebf005d067dd'),
    'coin_types' => [
        'BTC' => [
            'type' => 'utxo',
            'chain' => 'bitcoin'
        ],
        'DOGE' => [
            'type' => 'utxo',
            'chain' => 'dogecoin'
        ],
        'BNB' => [
            'type' => 'evm',
            'chain' => 'binance-smart-chain'
        ],
        'ETH' => [
            'type' => 'evm',
            'chain' => 'ethereum'
        ],
        'TRX' => [
            'type' => 'evm',
            'chain' => 'tron'
        ],
        'USDT' => [
            'type' => 'evm',
            'chain' => 'binance-smart-chain'
        ],
        'POL' => [
            'type' => 'evm',
            'chain' => 'polygon'
        ],
        'ARB' => [
            'type' => 'evm',
            'chain' => 'arbitrum'
        ],
        'OP' => [
            'type' => 'evm',
            'chain' => 'optimism'
        ],
        'AVAX' => [
            'type' => 'evm',
            'chain' => 'avalanche'
        ]
    ]
];
