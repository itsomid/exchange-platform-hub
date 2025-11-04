<?php

return [
    /*
    |--------------------------------------------------------------------------
    | In-Memory Order TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in seconds) bot orders will remain in
    | Redis before automatically expiring. This is a safety mechanism to
    | prevent orphaned orders if the cancellation logic fails.
    |
    | Note: Set this value to 0 (or a negative number) to store orders
    | without expiration. This helps prevent orphaned locked_balance when
    | Redis restarts or TTL-based expiry removes orders before cancellation.
    |
    | Default: 3600 seconds (1 hour)
    |
    */
    'in_memory_order_ttl' => env('SPOT_BOT_ORDER_TTL', 0),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The Redis connection to use for storing in-memory bot orders.
    | You can use a separate Redis database for bot orders to isolate them.
    |
    */
    'redis_connection' => env('SPOT_BOT_REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Enable In-Memory Orders
    |--------------------------------------------------------------------------
    |
    | Toggle to enable/disable in-memory order storage. If disabled, bot
    | orders will be stored in the database (old behavior).
    |
    | Set to false if you need to rollback to the old system.
    |
    */
    'enable_in_memory_orders' => env('SPOT_BOT_ENABLE_IN_MEMORY', true),

    /*
    |--------------------------------------------------------------------------
    | Maximum Orders per Market
    |--------------------------------------------------------------------------
    |
    | Maximum number of bot orders to fetch from Redis when matching.
    | Higher values increase matching accuracy but may impact performance.
    |
    */
    'max_orders_per_market' => env('SPOT_BOT_MAX_ORDERS_PER_MARKET', 100),

    /*
    |--------------------------------------------------------------------------
    | Auto-persist Threshold
    |--------------------------------------------------------------------------
    |
    | If an in-memory order reaches this fill percentage without being fully
    | matched, it will be automatically persisted to database to prevent loss.
    |
    | Value: 0-100 (percentage)
    | Set to 0 to disable auto-persist.
    |
    */
    'auto_persist_threshold' => env('SPOT_BOT_AUTO_PERSIST_THRESHOLD', 0),
];
