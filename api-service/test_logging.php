<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SpotBot\HybridOrderBookService;
use App\Services\SpotBot\InMemoryOrderBookService;
use App\Enums\SpotOrderSideEnum;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Logging for Empty Bids Issue ===\n";

$marketId = 1; // Change this to your actual market ID
$limit = 10;

echo "Market ID: {$marketId}\n";
echo "Limit: {$limit}\n";
echo "Check the Laravel logs for detailed tracking information.\n\n";

// Clear any previous logs for this test
\Log::info('=== STARTING EMPTY BIDS DEBUG SESSION ===', [
    'marketId' => $marketId,
    'timestamp' => now()->toDateTimeString(),
    'test_session_id' => uniqid('debug_')
]);

echo "1. Testing InMemoryOrderBookService directly...\n";
$inMemoryService = app(InMemoryOrderBookService::class);
$redisBids = $inMemoryService->getMarketOrders($marketId, SpotOrderSideEnum::BUY, $limit);
echo "   Redis bids found: " . count($redisBids) . "\n";

echo "\n2. Testing HybridOrderBookService...\n";
$hybridService = app(HybridOrderBookService::class);
$orderBook = $hybridService->getLatestOrderBook($marketId, $limit);
echo "   Final bids count: " . count($orderBook['bids']) . "\n";
echo "   Final asks count: " . count($orderBook['asks']) . "\n";

echo "\n3. Simulating API call...\n";
// Simulate the API controller call
$startTime = microtime(true);

\Log::info('[TEST] Simulating OrderController API call', [
    'marketId' => $marketId,
    'limit' => config('spot.order_book_limit_count', 50),
    'test_mode' => true
]);

$apiOrderBook = $hybridService->getLatestOrderBook($marketId, config('spot.order_book_limit_count', 50));
$executionTime = round((microtime(true) - $startTime) * 1000, 2);

\Log::info('[TEST] Simulated API call completed', [
    'marketId' => $marketId,
    'executionTimeMs' => $executionTime,
    'asksCount' => count($apiOrderBook['asks'] ?? []),
    'bidsCount' => count($apiOrderBook['bids'] ?? [])
]);

echo "   API simulation completed in {$executionTime}ms\n";
echo "   API bids count: " . count($apiOrderBook['bids']) . "\n";
echo "   API asks count: " . count($apiOrderBook['asks']) . "\n";

\Log::info('=== EMPTY BIDS DEBUG SESSION COMPLETED ===', [
    'marketId' => $marketId,
    'timestamp' => now()->toDateTimeString(),
    'summary' => [
        'redis_bids' => count($redisBids),
        'hybrid_bids' => count($orderBook['bids']),
        'api_bids' => count($apiOrderBook['bids'])
    ]
]);

echo "\n=== Test completed! ===\n";
echo "Check your Laravel logs (storage/logs/laravel.log) for detailed tracking information.\n";
echo "Look for logs with prefixes:\n";
echo "- [InMemoryOrderBookService]\n";
echo "- [HybridOrderBookService]\n";
echo "- [OrderController]\n";
echo "- [TEST]\n\n";

if (count($apiOrderBook['bids']) === 0) {
    echo "⚠️  WARNING: Bids are still empty! Check the logs to see where the data is lost.\n";
} else {
    echo "✅ SUCCESS: Bids are present (" . count($apiOrderBook['bids']) . " found)\n";
}