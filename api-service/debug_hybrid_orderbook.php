<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SpotBot\HybridOrderBookService;
use App\Services\SpotBot\InMemoryOrderBookService;
use App\Enums\SpotOrderSideEnum;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug HybridOrderBookService ===\n";

$marketId = 1; // Change this to your actual market ID
$limit = 10;

echo "Market ID: {$marketId}\n";
echo "Limit: {$limit}\n\n";

// Test HybridOrderBookService
$hybridService = app(HybridOrderBookService::class);
$orderBook = $hybridService->getLatestOrderBook($marketId, $limit);

echo "=== Order Book Results ===\n";
echo "Asks count: " . count($orderBook['asks']) . "\n";
echo "Bids count: " . count($orderBook['bids']) . "\n\n";

echo "=== Asks ===\n";
foreach ($orderBook['asks'] as $ask) {
    echo "Price: {$ask->price}, Quantity: {$ask->quantity}, Total: {$ask->total}\n";
}

echo "\n=== Bids ===\n";
foreach ($orderBook['bids'] as $bid) {
    echo "Price: {$bid->price}, Quantity: {$bid->quantity}, Total: {$bid->total}\n";
}

// Test individual components
echo "\n=== Testing Individual Components ===\n";

// Test InMemoryOrderBookService directly
$inMemoryService = app(InMemoryOrderBookService::class);
$redisBids = $inMemoryService->getMarketOrders($marketId, SpotOrderSideEnum::BUY, $limit);
echo "Redis bids count: " . count($redisBids) . "\n";
foreach ($redisBids as $bid) {
    echo "Redis Bid: ID={$bid->id}, Price={$bid->price}, Quantity={$bid->quantity}\n";
}

// Test database bids
echo "\n=== Database Bids ===\n";
$dbBids = \App\Models\SpotOrder::query()
    ->select('price', \Illuminate\Support\Facades\DB::raw('SUM(quantity - filled_quantity) as total_quantity'))
    ->where('market_id', $marketId)
    ->where('side', SpotOrderSideEnum::BUY)
    ->where('status', \App\Enums\SpotOrderStatusEnum::OPEN)
    ->whereRaw('quantity > filled_quantity')
    ->groupBy('price')
    ->orderBy('price', 'desc')
    ->limit($limit)
    ->get();

echo "Database bids count: " . count($dbBids) . "\n";
foreach ($dbBids as $bid) {
    echo "DB Bid: Price={$bid->price}, Total Quantity={$bid->total_quantity}\n";
}

echo "\n=== Debug Complete ===\n";