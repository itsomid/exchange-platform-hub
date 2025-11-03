<?php

use Illuminate\Support\Facades\Route;

Route::get('/mehdi', function () {
    return ['token' => \App\Helpers\JWT::new()->encode()];
});

Route::get('/test-mehdi', function () {
    $so = resolve(\App\Services\Spot\SpotService::class);
    $res = $so->getLatestOrderBook(1, config('spot.order_book_limit_count'));

    return $res;
});

// تست سفارش‌های Redis
Route::get('/test-redis-orders', function () {
    try {
        $redis = \Illuminate\Support\Facades\Redis::connection();

        // تست اتصال ساده
        $redis->ping();

        $response = [
            'redis_connected' => true,
            'redis_info' => [
                'host' => config('database.redis.default.host'),
                'port' => config('database.redis.default.port'),
                'database' => config('database.redis.default.database'),
            ],
            'orders' => [],
            'market_orders' => [],
            'all_keys' => [],
        ];

        // گرفتن کلیدها با روش ساده‌تر
        try {
            $allKeys = $redis->keys('spot_bot*');
            $response['all_keys'] = $allKeys ?? [];
            $response['total_keys'] = count($allKeys ?? []);
        } catch (\Exception $e) {
            $response['keys_error'] = $e->getMessage();
            $response['all_keys'] = [];
        }

        $buyOrdersKey = 'spot_bot_market_orders:4:buy';
        $sellOrdersKey = 'spot_bot_market_orders:4:sell';

        try {
            $buyIds = $redis->zrevrange($buyOrdersKey, 0, 10) ?: [];
            $sellIds = $redis->zrange($sellOrdersKey, 0, 10) ?: [];

            $response['market_orders'] = [
                'buy_order_ids' => $buyIds,
                'sell_order_ids' => $sellIds,
                'buy_count' => count($buyIds),
                'sell_count' => count($sellIds),
            ];


            $allOrderIds = array_merge($buyIds, $sellIds);
            foreach ($allOrderIds as $orderId) {
                if (empty($orderId)) continue;

                $orderData = $redis->get("spot_bot_orders:{$orderId}");
                if ($orderData) {
                    $response['orders'][] = json_decode($orderData, true);
                }
            }
        } catch (\Exception $e) {
            $response['orders_error'] = $e->getMessage();
        }

        return response()->json($response);

    } catch (\Exception $e) {
        return response()->json([
            'redis_connected' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
        ], 500);
    }
});

// تست ایجاد سفارش در Redis
Route::get('/test-create-redis-order', function () {
    try {
        $inMemoryOrderBook = resolve(\App\Services\SpotBot\InMemoryOrderBookService::class);

        // ایجاد یک سفارش تست
        $order = new \App\Services\SpotBot\DTO\InMemoryBotOrderDTO([
            'id' => $inMemoryOrderBook->generateOrderId(),
            'user_id' => 1,
            'market_id' => 1,
            'quantity' => '0.5',
            'filled_quantity' => '0',
            'price' => '50000',
            'side' => \App\Enums\SpotOrderSideEnum::BUY,
            'type' => \App\Enums\SpotOrderTypeEnum::LIMIT,
            'status' => \App\Enums\SpotOrderStatusEnum::OPEN,
            'created_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ]);

        $stored = $inMemoryOrderBook->storeOrder($order);

        // بررسی ذخیره شده یا نه
        $retrieved = $inMemoryOrderBook->getOrder($order->id);

        return response()->json([
            'success' => true,
            'stored' => $stored,
            'order_id' => $order->id,
            'retrieved' => $retrieved ? $retrieved->toArray() : null,
            'exists' => $inMemoryOrderBook->orderExists($order->id),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// تست aggregation سفارش‌های هم‌قیمت در order book
Route::get('/test-orderbook-aggregation', function () {
    try {
        $hybridOrderBook = resolve(\App\Services\SpotBot\HybridOrderBookService::class);

        // گرفتن order book برای market 4 (TRX)
        $orderBook = $hybridOrderBook->getLatestOrderBook(4, 20);

        // پیدا کردن قیمت 0.319594
        $targetPrice = '0.319594';
        $foundInAsks = null;
        $foundInBids = null;

        foreach ($orderBook['asks'] as $ask) {
            if (bccomp($ask->price, $targetPrice, 8) === 0) {
                $foundInAsks = $ask;
                break;
            }
        }

        foreach ($orderBook['bids'] as $bid) {
            if (bccomp($bid->price, $targetPrice, 8) === 0) {
                $foundInBids = $bid;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'target_price' => $targetPrice,
            'found_in_asks' => $foundInAsks ? [
                'price' => $foundInAsks->price,
                'quantity' => $foundInAsks->quantity,
                'total' => $foundInAsks->total,
            ] : null,
            'found_in_bids' => $foundInBids ? [
                'price' => $foundInBids->price,
                'quantity' => $foundInBids->quantity,
                'total' => $foundInBids->total,
            ] : null,
            'all_asks_count' => count($orderBook['asks']),
            'all_bids_count' => count($orderBook['bids']),
            'asks_prices' => array_map(fn($a) => $a->price, array_slice($orderBook['asks'], 0, 5)),
            'bids_prices' => array_map(fn($b) => $b->price, array_slice($orderBook['bids'], 0, 5)),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::view('/web-socket', 'welcome');
Route::get('test-dis', function () {
    \App\Events\OrderBookUpdated::dispatch(1);
});
Route::get('/debug-sentry', function () {
    throw new Exception('Test error from Laravel 11');
});
