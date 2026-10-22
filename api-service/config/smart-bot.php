<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reference exchange driver
    |--------------------------------------------------------------------------
    | Which implementation of ExchangeContract the smart trading bot uses.
    |   - "binance" → real omnibus Binance account (production)
    |   - "fake"    → in-memory FakeBotExchange for the admin Test Lab harness
    */
    'exchange_driver' => env('BOT_EXCHANGE_DRIVER', 'binance'),

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

    /*
    |--------------------------------------------------------------------------
    | Waiting sell tiers
    |--------------------------------------------------------------------------
    | Tiers priced above Binance's PERCENT_PRICE_BY_SIDE ceiling stay WAITING
    | until bot:place-waiting-sells finds them in range.
    |   - safety_margin_percent: a tier is placed only when its price is this
    |     far below the ceiling (the average price moves until the order lands)
    |   - max_per_run: exchange orders one cycle may place
    */
    'waiting' => [
        'safety_margin_percent' => (float) env('BOT_WAITING_SAFETY_MARGIN_PERCENT', 2),
        'max_per_run'           => (int) env('BOT_WAITING_MAX_PER_RUN', 200),
    ],
];
