<?php

namespace App\Services\SpotBot;

use App\Services\SpotBot\DTO\InMemoryBotOrderDTO;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Redis\Connections\Connection;

/**
 * Service to manage bot orders in Redis (in-memory) instead of database
 * This prevents unnecessary database writes for orders that will be cancelled
 */
class InMemoryOrderBookService
{
    private const REDIS_PREFIX = 'spot_bot_orders';
    private const MARKET_ORDERS_PREFIX = 'spot_bot_market_orders';
    private const USER_ORDERS_PREFIX = 'spot_bot_user_orders';

    /**
     * Get Redis connection for bot orders
     */
    private function getRedisConnection(): Connection
    {
        $connection = config('spot-bot.redis_connection', 'default');
        return Redis::connection($connection);
    }

    /**
     * Get TTL from config
     */
    private function getOrderTTL(): int
    {
        return config('spot-bot.in_memory_order_ttl', 3600);
    }

    /**
     * Store a bot order in Redis
     */
    public function storeOrder(InMemoryBotOrderDTO $order): bool
    {
        $redis = $this->getRedisConnection();
        $orderId = $order->id;
        $orderKey = $this->getOrderKey($orderId);
        $ttl = $this->getOrderTTL();

        // Store the order data with or without TTL based on config
        if ($ttl > 0) {
            $redis->setex($orderKey, $ttl, json_encode($order->toArray()));
        } else {
            // TTL <= 0 means keep orders without expiration to avoid orphaned locks
            $redis->set($orderKey, json_encode($order->toArray()));
        }

        // Add to market index (for matching engine)
        $marketKey = $this->getMarketOrdersKey($order->market_id, $order->side);
        $redis->zadd($marketKey, [$orderId => $order->price]);
        if ($ttl > 0) {
            $redis->expire($marketKey, $ttl);
        }

        // Add to user index (for cancellation)
        $userKey = $this->getUserOrdersKey($order->user_id, $order->market_id);
        $redis->sadd($userKey, $orderId);
        if ($ttl > 0) {
            $redis->expire($userKey, $ttl);
        }

        return true;
    }

    /**
     * Get an order by ID
     */
    public function getOrder(string $orderId): ?InMemoryBotOrderDTO
    {
        $redis = $this->getRedisConnection();
        $orderKey = $this->getOrderKey($orderId);
        $data = $redis->get($orderKey);

        if (!$data) {
            return null;
        }

        return InMemoryBotOrderDTO::fromArray(json_decode($data, true));
    }

    /**
     * Get orders for a market sorted by price (for matching)
     * 
     * @param int $marketId
     * @param SpotOrderSideEnum $side
     * @param int $limit
     * @return array<InMemoryBotOrderDTO>
     */
    public function getMarketOrders(int $marketId, SpotOrderSideEnum $side, int $limit = 100): array
    {
        \Log::info('[InMemoryOrderBookService] getMarketOrders called', [
            'marketId' => $marketId,
            'side' => $side->value,
            'limit' => $limit
        ]);

        $redis = $this->getRedisConnection();
        $marketKey = $this->getMarketOrdersKey($marketId, $side);

        \Log::info('[InMemoryOrderBookService] Redis key generated', [
            'marketKey' => $marketKey,
            'marketId' => $marketId,
            'side' => $side->value
        ]);

        // Check if key exists in Redis
        $keyExists = $redis->exists($marketKey);
        \Log::info('[InMemoryOrderBookService] Redis key existence check', [
            'marketKey' => $marketKey,
            'exists' => $keyExists
        ]);

        // For BUY orders: get highest prices first (DESC)
        // For SELL orders: get lowest prices first (ASC)
        $orderIds = $side === SpotOrderSideEnum::BUY
            ? $redis->zrevrange($marketKey, 0, $limit - 1)
            : $redis->zrange($marketKey, 0, $limit - 1);

        \Log::info('[InMemoryOrderBookService] Order IDs retrieved from Redis', [
            'marketKey' => $marketKey,
            'side' => $side->value,
            'orderIds' => $orderIds,
            'count' => count($orderIds)
        ]);

        $orders = [];
        $processedCount = 0;
        $openOrdersCount = 0;

        foreach ($orderIds as $orderId) {
            $processedCount++;
            $order = $this->getOrder($orderId);

            \Log::debug('[InMemoryOrderBookService] Processing order', [
                'orderId' => $orderId,
                'orderFound' => $order !== null,
                'orderStatus' => $order ? $order->status->value : null,
                'processedCount' => $processedCount
            ]);

            if ($order && $order->status === SpotOrderStatusEnum::OPEN) {
                $orders[] = $order;
                $openOrdersCount++;
            } elseif (!$order) {
                // Self-heal: remove stale orderId from market index when order key is missing
                try {
                    $redis->zrem($marketKey, $orderId);
                    \Log::warning('[InMemoryOrderBookService] Removed stale orderId from market index', [
                        'marketKey' => $marketKey,
                        'orderId' => $orderId
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('[InMemoryOrderBookService] Failed to remove stale orderId', [
                        'marketKey' => $marketKey,
                        'orderId' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        \Log::info('[InMemoryOrderBookService] getMarketOrders completed', [
            'marketId' => $marketId,
            'side' => $side->value,
            'totalOrderIds' => count($orderIds),
            'processedOrders' => $processedCount,
            'openOrders' => $openOrdersCount,
            'finalOrdersCount' => count($orders)
        ]);

        return $orders;
    }

    /**
     * Clear the market orders ZSET index for a specific side
     */
    public function clearMarketOrdersIndex(int $marketId, SpotOrderSideEnum $side): bool
    {
        $redis = $this->getRedisConnection();
        $marketKey = $this->getMarketOrdersKey($marketId, $side);

        try {
            $redis->del($marketKey);
            \Log::info('[InMemoryOrderBookService] Cleared market orders index', [
                'marketId' => $marketId,
                'side' => $side->value,
                'marketKey' => $marketKey
            ]);
            return true;
        } catch (\Throwable $e) {
            \Log::error('[InMemoryOrderBookService] Failed to clear market orders index', [
                'marketId' => $marketId,
                'side' => $side->value,
                'marketKey' => $marketKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clearUserMarketOrdersIndex(int $userId, int $marketId): bool
    {
        $redis = $this->getRedisConnection();
        $userKey = $this->getUserOrdersKey($userId, $marketId);

        try {
            $redis->del($userKey);
            \Log::info('[InMemoryOrderBookService] Cleared user orders index', [
                'userId' => $userId,
                'marketId' => $marketId,
                'userKey' => $userKey
            ]);
            return true;
        } catch (\Throwable $e) {
            \Log::error('[InMemoryOrderBookService] Failed to clear user orders index', [
                'userId' => $userId,
                'marketId' => $marketId,
                'userKey' => $userKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all open orders for a user in a specific market
     * 
     * @param int $userId
     * @param int $marketId
     * @return array<InMemoryBotOrderDTO>
     */
    public function getUserMarketOrders(int $userId, int $marketId): array
    {
        $redis = $this->getRedisConnection();
        $userKey = $this->getUserOrdersKey($userId, $marketId);
        $orderIds = $redis->smembers($userKey);

        $orders = [];
        foreach ($orderIds as $orderId) {
            $order = $this->getOrder($orderId);
            if ($order && $order->status === SpotOrderStatusEnum::OPEN) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    /**
     * Update an order (typically for filled quantity)
     */
    public function updateOrder(InMemoryBotOrderDTO $order): bool
    {
        $redis = $this->getRedisConnection();
        $orderKey = $this->getOrderKey($order->id);
        $order->updated_at = now()->timestamp;
        $ttl = $this->getOrderTTL();

        if ($ttl > 0) {
            return $redis->setex($orderKey, $ttl, json_encode($order->toArray()));
        }

        return $redis->set($orderKey, json_encode($order->toArray()));
    }

    /**
     * Delete an order from Redis
     */
    public function deleteOrder(string $orderId): bool
    {
        $redis = $this->getRedisConnection();
        $order = $this->getOrder($orderId);
        if (!$order) {
            return false;
        }

        // Remove from main storage
        $orderKey = $this->getOrderKey($orderId);
        $redis->del($orderKey);

        // Remove from market index
        $marketKey = $this->getMarketOrdersKey($order->market_id, $order->side);
        $redis->zrem($marketKey, $orderId);

        // Remove from user index
        $userKey = $this->getUserOrdersKey($order->user_id, $order->market_id);
        $redis->srem($userKey, $orderId);

        return true;
    }

    /**
     * Delete all orders for a user in a specific market
     */
    public function deleteUserMarketOrders(int $userId, int $marketId): int
    {
        $redis = $this->getRedisConnection();
        $userKey = $this->getUserOrdersKey($userId, $marketId);
        $orderIds = $redis->smembers($userKey);
        $count = 0;

        foreach ($orderIds as $orderId) {
            if ($this->deleteOrder($orderId)) {
                $count++;
            } else {
                try {
                    $redis->srem($userKey, $orderId);
                } catch (\Throwable $e) {
                }
            }
        }

        return $count;
    }

    /**
     * Generate a unique order ID
     */
    public function generateOrderId(): string
    {
        return 'bot_' . now()->timestamp . '_' . Str::random(8);
    }

    /**
     * Check if an order exists in Redis
     */
    public function orderExists(string $orderId): bool
    {
        $redis = $this->getRedisConnection();
        return $redis->exists($this->getOrderKey($orderId)) > 0;
    }

    /**
     * Get Redis key for an order
     */
    private function getOrderKey(string $orderId): string
    {
        return self::REDIS_PREFIX . ':' . $orderId;
    }

    /**
     * Get Redis key for market orders (sorted set by price)
     */
    private function getMarketOrdersKey(int $marketId, SpotOrderSideEnum $side): string
    {
        return self::MARKET_ORDERS_PREFIX . ':' . $marketId . ':' . $side->value;
    }

    /**
     * Get Redis key for user orders in a market
     */
    private function getUserOrdersKey(int $userId, int $marketId): string
    {
        return self::USER_ORDERS_PREFIX . ':' . $userId . ':' . $marketId;
    }

    /**
     * Get count of orders in Redis for monitoring
     */
    public function getOrderCount(int $marketId, SpotOrderSideEnum $side): int
    {
        $redis = $this->getRedisConnection();
        $marketKey = $this->getMarketOrdersKey($marketId, $side);
        return $redis->zcard($marketKey);
    }
}
