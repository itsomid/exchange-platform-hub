<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reference exchange driver
    |--------------------------------------------------------------------------
    | Which implementation of ExchangeContract the smart trading bot uses.
    |   - "coinex" → real omnibus CoinEx account (production)
    |   - "fake"   → in-memory FakeBotExchange for the admin Test Lab harness
    */
    'exchange_driver' => env('BOT_EXCHANGE_DRIVER', 'coinex'),

    /*
    |--------------------------------------------------------------------------
    | Internal test-lab token
    |--------------------------------------------------------------------------
    | Shared secret used by admin-panel's Test Lab to call the protected
    | /api/internal/bot-test/* endpoints. Must match BOT_INTERNAL_TEST_TOKEN
    | in admin-panel's env. Empty disables the endpoints entirely.
    */
    'internal_test_token' => env('BOT_INTERNAL_TEST_TOKEN', ''),
];
