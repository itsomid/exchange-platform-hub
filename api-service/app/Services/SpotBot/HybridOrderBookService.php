<?php

namespace App\Services\SpotBot;

use App\Services\SpotBot\InMemoryOrderBookService;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Models\SpotOrder;
use App\Helpers\Math;
use Illuminate\Support\Facades\DB;

/**
 * Service to get order book data from both database and Redis
 * This ensures bot orders (in Redis) are visible in the order book
 */
class HybridOrderBookService
{
    public function __construct(
        private readonly InMemoryOrderBookService $inMemoryOrderBook,
    ) {}

    /**
     * Get latest order book combining database and Redis orders
     * 
     * @param int $marketId
     * @param int $limit
     * @return array ['asks' => [...], 'bids' => [...]]
     */
    public function getLatestOrderBook(int $marketId, int $limit): array
    {
        // Get asks (sell orders) from both sources
        $dbAsks = SpotOrder::query()
            ->select('price', DB::raw('SUM(quantity - filled_quantity) as total_quantity'))
            ->where('market_id', $marketId)
            ->where('side', SpotOrderSideEnum::SELL)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->whereRaw('quantity > filled_quantity')
            ->groupBy('price')
            ->orderBy('price', 'asc')
            ->limit($limit)
            ->get();

        // Get Redis asks
        $redisAsks = $this->inMemoryOrderBook->getMarketOrders($marketId, SpotOrderSideEnum::SELL, $limit);

        \Log::info('[HybridOrderBookService] Retrieved asks data', [
            'marketId' => $marketId,
            'dbAsks' => count($dbAsks),
            'redisAsks' => count($redisAsks)
        ]);

        // Combine and group asks by price
        $asksGrouped = $this->combineAndGroupOrders($dbAsks, $redisAsks, 'asc', $limit);

        \Log::info('[HybridOrderBookService] Asks grouped', [
            'marketId' => $marketId,
            'asksGroupedCount' => count($asksGrouped)
        ]);

        // Get bids (buy orders) from both sources
        $dbBids = SpotOrder::query()
            ->select('price', DB::raw('SUM(quantity - filled_quantity) as total_quantity'))
            ->where('market_id', $marketId)
            ->where('side', SpotOrderSideEnum::BUY)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->whereRaw('quantity > filled_quantity')
            ->groupBy('price')
            ->orderBy('price', 'desc')
            ->limit($limit)
            ->get();

        // Get Redis bids
        $redisBids = $this->inMemoryOrderBook->getMarketOrders($marketId, SpotOrderSideEnum::BUY, $limit);
        
        \Log::info('[HybridOrderBookService] Retrieved bids data', [
            'marketId' => $marketId,
            'dbBids' => count($dbBids),
            'redisBids' => count($redisBids),
            'redisBidsData' => array_map(function($bid) {
                return [
                    'id' => $bid->id,
                    'price' => $bid->price,
                    'quantity' => $bid->quantity,
                    'status' => $bid->status->value
                ];
            }, $redisBids)
        ]);
        
        // Combine and group bids by price
        $bidsGrouped = $this->combineAndGroupOrders($dbBids, $redisBids, 'desc', $limit);

        \Log::info('[HybridOrderBookService] Bids grouped', [
            'marketId' => $marketId,
            'bidsGroupedCount' => count($bidsGrouped),
            'bidsGroupedData' => $bidsGrouped
        ]);

        \Log::info('[HybridOrderBookService] getLatestOrderBook completed', [
            'marketId' => $marketId,
            'finalResult' => [
                'asks' => count($asksGrouped),
                'bids' => count($bidsGrouped)
            ]
        ]);

        return [
            'asks' => $asksGrouped,
            'bids' => $bidsGrouped,
        ];
    }

    /**
     * Normalize price to a consistent format for grouping
     * Uses bcmath to ensure exact price matching for grouping
     * This ensures prices like "0.319594" and "0.3195940" are treated as the same
     * 
     * @param string $price
     * @return string Normalized price key
     */
    private function normalizePriceKey(string $price): string
    {
        // Use bcmath to normalize the price
        // First, round to remove any floating point precision issues
        // Use high precision (16 decimal places) for grouping key
        $normalized = bcadd($price, '0', 16);

        // Remove trailing zeros but keep decimal if needed
        $normalized = rtrim($normalized, '0');
        $normalized = rtrim($normalized, '.');

        // If no decimal part, add .0 for consistency
        if (strpos($normalized, '.') === false) {
            $normalized .= '.0';
        }

        return $normalized;
    }

    /**
     * Combine database and Redis orders, group by price level
     * 
     * @param \Illuminate\Support\Collection $dbOrders
     * @param array $redisOrders
     * @param string $sortDirection 'asc' or 'desc'
     * @param int $limit
     * @return array
     */
    private function combineAndGroupOrders($dbOrders, array $redisOrders, string $sortDirection, int $limit): array
    {
        \Log::info('[HybridOrderBookService] combineAndGroupOrders started', [
            'dbOrdersCount' => count($dbOrders),
            'redisOrdersCount' => count($redisOrders),
            'sortDirection' => $sortDirection,
            'limit' => $limit
        ]);

        $grouped = [];

        // Process database orders
        $dbProcessedCount = 0;
        foreach ($dbOrders as $order) {
            $dbProcessedCount++;
            $price = (string) $order->price;
            $priceKey = $this->normalizePriceKey($price);

            \Log::debug('[HybridOrderBookService] Processing DB order', [
                'orderPrice' => $price,
                'priceKey' => $priceKey,
                'totalQuantity' => $order->total_quantity ?? '0',
                'processedCount' => $dbProcessedCount
            ]);

            if (!isset($grouped[$priceKey])) {
                $grouped[$priceKey] = [
                    'price' => $price, // Keep original price for display
                    'quantity' => '0',
                    'total' => '0',
                ];
            }
            $grouped[$priceKey]['quantity'] = Math::add($grouped[$priceKey]['quantity'], $order->total_quantity ?? '0');
        }

        \Log::info('[HybridOrderBookService] DB orders processed', [
            'processedCount' => $dbProcessedCount,
            'groupedPriceLevels' => count($grouped)
        ]);

        // Process Redis orders
        $redisProcessedCount = 0;
        foreach ($redisOrders as $order) {
            $redisProcessedCount++;
            $price = (string) $order->price;
            $priceKey = $this->normalizePriceKey($price);

            // Calculate remaining quantity
            $remainingQty = Math::sub($order->quantity, $order->filled_quantity);

            \Log::debug('[HybridOrderBookService] Processing Redis order', [
                'orderId' => $order->id,
                'orderPrice' => $price,
                'priceKey' => $priceKey,
                'quantity' => $order->quantity,
                'filledQuantity' => $order->filled_quantity,
                'remainingQty' => $remainingQty,
                'processedCount' => $redisProcessedCount
            ]);

            if (!isset($grouped[$priceKey])) {
                $grouped[$priceKey] = [
                    'price' => $price, // Keep original price for display
                    'quantity' => '0',
                    'total' => '0',
                ];
            }
            
            $grouped[$priceKey]['quantity'] = Math::add($grouped[$priceKey]['quantity'], $remainingQty);
        }

        \Log::info('[HybridOrderBookService] Redis orders processed', [
            'processedCount' => $redisProcessedCount,
            'finalGroupedPriceLevels' => count($grouped),
            'groupedData' => $grouped
        ]);

        // Calculate total (cumulative) and convert to array
        $result = array_values($grouped);

        \Log::info('[HybridOrderBookService] Before sorting', [
            'resultCount' => count($result),
            'sortDirection' => $sortDirection
        ]);

        // Sort by price
        usort($result, function ($a, $b) use ($sortDirection) {
            $comp = Math::comp($a['price'], $b['price']);
            return $sortDirection === 'asc' ? $comp : -$comp;
        });

        \Log::info('[HybridOrderBookService] After sorting', [
            'resultCount' => count($result)
        ]);

        // Calculate cumulative total and limit
        $cumulative = '0';
        $limited = [];
        foreach ($result as $level) {
            if (count($limited) >= $limit) {
                break;
            }
            $cumulative = Math::add($cumulative, $level['quantity']);
            $level['total'] = $cumulative;
            $limited[] = (object) $level;
        }

        \Log::info('[HybridOrderBookService] combineAndGroupOrders completed', [
            'finalCount' => count($limited),
            'finalData' => $limited
        ]);

        return $limited;
    }

    /**
     * Get aggregated order book for chart/depth visualization
     * Groups orders into price levels with tick size
     * 
     * @param int $marketId
     * @param string $tickSize Price aggregation level (e.g., '0.1', '1', '10')
     * @param int $limit
     * @return array
     */
    public function getAggregatedOrderBook(int $marketId, string $tickSize = '1', int $limit = 50): array
    {
        $orderBook = $this->getLatestOrderBook($marketId, $limit * 2);

        return [
            'asks' => $this->aggregateByTickSize($orderBook['asks'], $tickSize, 'ceil'),
            'bids' => $this->aggregateByTickSize($orderBook['bids'], $tickSize, 'floor'),
        ];
    }

    /**
     * Aggregate orders by tick size (price rounding)
     * 
     * @param array $orders
     * @param string $tickSize
     * @param string $roundingMethod 'ceil' or 'floor'
     * @return array
     */
    private function aggregateByTickSize(array $orders, string $tickSize, string $roundingMethod): array
    {
        if (Math::comp($tickSize, '0') <= 0) {
            return $orders;
        }

        $aggregated = [];

        foreach ($orders as $order) {
            // Round price to tick size
            $divided = bcdiv($order->price, $tickSize, 0);
            if ($roundingMethod === 'ceil') {
                $rounded = bcmul(bcadd($divided, '1', 0), $tickSize, 8);
            } else {
                $rounded = bcmul($divided, $tickSize, 8);
            }

            if (!isset($aggregated[$rounded])) {
                $aggregated[$rounded] = [
                    'price' => $rounded,
                    'quantity' => '0',
                    'total' => '0',
                ];
            }

            $aggregated[$rounded]['quantity'] = Math::add($aggregated[$rounded]['quantity'], $order->quantity);
        }

        // Recalculate totals
        $result = array_values($aggregated);
        $cumulative = '0';
        foreach ($result as &$level) {
            $cumulative = Math::add($cumulative, $level['quantity']);
            $level['total'] = $cumulative;
            $level = (object) $level;
        }

        return $result;
    }

    /**
     * Get order book depth summary (for quick market overview)
     * 
     * @param int $marketId
     * @return array
     */
    public function getOrderBookDepth(int $marketId): array
    {
        $orderBook = $this->getLatestOrderBook($marketId, 20);

        $bestAsk = isset($orderBook['asks'][0]) ? $orderBook['asks'][0]->price : null;
        $bestBid = isset($orderBook['bids'][0]) ? $orderBook['bids'][0]->price : null;

        $spread = null;
        $spreadPercentage = null;

        if ($bestAsk && $bestBid) {
            $spread = Math::sub($bestAsk, $bestBid);
            $spreadPercentage = Math::mul(Math::div($spread, $bestBid, 8), '100');
        }

        return [
            'best_ask' => $bestAsk,
            'best_bid' => $bestBid,
            'spread' => $spread,
            'spread_percentage' => $spreadPercentage,
            'asks_depth' => count($orderBook['asks']),
            'bids_depth' => count($orderBook['bids']),
            'total_ask_volume' => $this->calculateTotalVolume($orderBook['asks']),
            'total_bid_volume' => $this->calculateTotalVolume($orderBook['bids']),
        ];
    }

    /**
     * Calculate total volume from order book side
     * 
     * @param array $orders
     * @return string
     */
    private function calculateTotalVolume(array $orders): string
    {
        $total = '0';
        foreach ($orders as $order) {
            $total = Math::add($total, $order->quantity);
        }
        return $total;
    }

    /**
     * Check if the opposite side of order book has any open orders (both DB and Redis)
     * For BUY market orders, check if there are any SELL orders (asks)
     * For SELL market orders, check if there are any BUY orders (bids)
     * 
     * @param int $marketId
     * @param SpotOrderSideEnum $orderSide The side of the incoming order
     * @param int|null $excludeUserId User ID to exclude from the check (prevent self-trading)
     * @return bool
     */
    public function hasOrdersOnOppositeSide(int $marketId, SpotOrderSideEnum $orderSide, ?int $excludeUserId = null): bool
    {
        $oppositeSide = $orderSide === SpotOrderSideEnum::BUY
            ? SpotOrderSideEnum::SELL
            : SpotOrderSideEnum::BUY;

        // Check database orders
        $dbQuery = SpotOrder::query()
            ->where('market_id', $marketId)
            ->where('side', $oppositeSide)
            ->where('status', SpotOrderStatusEnum::OPEN)
            ->whereRaw('quantity > filled_quantity');

        if ($excludeUserId !== null) {
            $dbQuery->where('user_id', '!=', $excludeUserId);
        }

        if ($dbQuery->exists()) {
            return true;
        }

        // Check Redis orders (bot orders)
        $redisOrders = $this->inMemoryOrderBook->getMarketOrders($marketId, $oppositeSide, 1);

        if (empty($redisOrders)) {
            return false;
        }

        // If excludeUserId is set, check if any Redis order is from a different user
        if ($excludeUserId !== null) {
            foreach ($redisOrders as $order) {
                if ($order->user_id !== $excludeUserId) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }
}
