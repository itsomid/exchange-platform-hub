<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Redis;
use App\Services\SpotBot\InMemoryOrderBookService;
use App\Enums\SpotOrderSideEnum;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Redis Orders ===\n";

// Test parameters - adjust these based on your actual market ID
$marketId = 1; // Change this to your actual market ID
$side = SpotOrderSideEnum::BUY;

echo "Market ID: {$marketId}\n";
echo "Side: {$side->value}\n";

// Get Redis connection
$redis = Redis::connection(config('spot-bot.redis_connection', 'default'));

// Check Redis keys
$marketKey = 'spot_bot_market_orders:' . $marketId . ':' . $side->value;
echo "Redis Key: {$marketKey}\n";

// Check if key exists
$exists = $redis->exists($marketKey);
echo "Key exists: " . ($exists ? 'YES' : 'NO') . "\n";

if ($exists) {
    // Get count of orders
    $count = $redis->zcard($marketKey);
    echo "Order count: {$count}\n";
    
    // Get all order IDs
    $orderIds = $redis->zrange($marketKey, 0, -1);
    echo "Order IDs: " . json_encode($orderIds) . "\n";
    
    // Get orders with scores (prices)
    $ordersWithScores = $redis->zrange($marketKey, 0, -1, ['withscores' => true]);
    echo "Orders with prices: " . json_encode($ordersWithScores) . "\n";
    
    // Check individual orders
    foreach ($orderIds as $orderId) {
        $orderKey = 'spot_bot_orders:' . $orderId;
        $orderData = $redis->get($orderKey);
        if ($orderData) {
            $order = json_decode($orderData, true);
            echo "Order {$orderId}: " . json_encode($order) . "\n";
        } else {
            echo "Order {$orderId}: NOT FOUND\n";
        }
    }
} else {
    echo "No orders found in Redis for this market and side.\n";
    
    // Check all Redis keys matching pattern
    $pattern = 'spot_bot_market_orders:*';
    $allKeys = $redis->keys($pattern);
    echo "All market order keys in Redis: " . json_encode($allKeys) . "\n";
}

// Test InMemoryOrderBookService directly
echo "\n=== Testing InMemoryOrderBookService ===\n";
$service = app(InMemoryOrderBookService::class);
$orders = $service->getMarketOrders($marketId, $side, 10);
echo "Orders from service: " . count($orders) . "\n";
foreach ($orders as $order) {
    echo "Order: ID={$order->id}, Price={$order->price}, Quantity={$order->quantity}, Status={$order->status->value}\n";
}

echo "\n=== Debug Complete ===\n";