<?php

namespace App\Services\Exchanges\Asset\Contract;

/**
 * Orders and deals are returned in CoinEx v2 shape (order_id, market, side, type,
 * amount, price, filled_amount, unfilled_amount, filled_value, created_at, ...)
 * so the admin views stay exchange-agnostic.
 */
interface SpotOrderServiceInterface
{
    /**
     * @return array{data: list<array>, pagination: array{total?: int, has_next: bool}}
     */
    public function getPendingOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array;

    /**
     * @return array{data: list<array>, pagination: array{total?: int, has_next: bool}}
     */
    public function getFinishedOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array;

    /**
     * @return array{base: array{ccy: string, available: string, frozen: string, total: string}, usdt: array{ccy: string, available: string, frozen: string, total: string}}
     */
    public function getMarketBalances(string $baseSymbol): array;

    public function cancelOrder(string $market, int|string $orderId): array;

    /**
     * @param  list<string>  $markets
     * @return array{market: string, order: array}
     */
    public function findOrderById(int|string $orderId, ?string $market = null, array $markets = []): array;

    /**
     * @return array{data: list<array>, pagination: array{has_next: bool}}
     */
    public function getOrderDeals(string $market, int|string $orderId, int $page = 1, int $limit = 100): array;
}
