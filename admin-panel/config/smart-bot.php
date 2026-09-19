<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API service base URL
    |--------------------------------------------------------------------------
    | Base URL of api-service used by the admin Test Lab to call the
    | /api/internal/bot-test/* endpoints.
    */
    'api_url' => env('BOT_API_BASE_URL', 'http://127.0.0.1:8001'),

    /*
    |--------------------------------------------------------------------------
    | Internal test-lab token
    |--------------------------------------------------------------------------
    | Shared secret sent as X-Internal-Token. Must match
    | BOT_INTERNAL_TEST_TOKEN / config('smart-bot.internal_test_token')
    | on api-service. Empty disables Test Lab calls.
    */
    'internal_test_token' => env('BOT_INTERNAL_TEST_TOKEN', 'a1b2c3d4e5f6g7h'),

    /*
    |--------------------------------------------------------------------------
    | Internal admin token
    |--------------------------------------------------------------------------
    | Shared secret sent as X-Internal-Token to the /api/internal/bot-admin/*
    | endpoints (order cancel / liquidation — also active in production).
    | Must match BOT_INTERNAL_ADMIN_TOKEN on api-service. Falls back to the
    | test-lab token when not set.
    */
    'internal_admin_token' => env('BOT_INTERNAL_ADMIN_TOKEN', env('BOT_INTERNAL_TEST_TOKEN', 'a1b2c3d4e5f6g7h')),

    /*
    |--------------------------------------------------------------------------
    | Default test user
    |--------------------------------------------------------------------------
    | User id pre-selected in the Test Lab UI when no user is specified.
    */
    'test_user_id' => env('BOT_TEST_USER_ID', 2),
];
