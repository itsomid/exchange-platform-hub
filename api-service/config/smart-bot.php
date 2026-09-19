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

    /*
    |--------------------------------------------------------------------------
    | Internal admin token
    |--------------------------------------------------------------------------
    | Shared secret used by admin-panel to call the protected
    | /api/internal/bot-admin/* endpoints (order cancel / liquidation).
    | Unlike the test-lab token these endpoints DO work in production.
    | Falls back to BOT_INTERNAL_TEST_TOKEN when not set.
    */
    'internal_admin_token' => env('BOT_INTERNAL_ADMIN_TOKEN', env('BOT_INTERNAL_TEST_TOKEN', '')),
];
