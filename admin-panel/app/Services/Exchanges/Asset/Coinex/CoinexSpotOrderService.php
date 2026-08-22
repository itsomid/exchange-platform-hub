<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class CoinexSpotOrderService
{
    public function getPendingOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array
    {
        return $this->listOrders('/v2/spot/pending-order', $market, $side, $page, $limit);
    }

    public function getFinishedOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array
    {
        return $this->listOrders('/v2/spot/finished-order', $market, $side, $page, $limit);
    }

    /**
     * Spot balances for a market's base coin and USDT.
     *
     * @return array{base: array{ccy: string, available: string, frozen: string, total: string}, usdt: array{ccy: string, available: string, frozen: string, total: string}}
     */
    public function getMarketBalances(string $baseSymbol): array
    {
        $baseSymbol = strtoupper($baseSymbol);

        try {
            $response = CoinexRequest::send(MethodEnum::GET, '/v2/assets/spot/balance');
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if (!$response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->error('Coinex spot balance failed', [
                'body' => $response->body(),
            ]);

            $message = $response->json('message') ?: 'خطا در دریافت موجودی از CoinEx';
            $mapped = CoinexError::tryFrom((int) $response->json('code'));
            if ($mapped) {
                $message = CoinexError::mapErrorToResponse($mapped);
            }

            throw new CantResolveCoinexException($message, (int) $response->json('code'));
        }

        $byCcy = [];
        foreach ($response->json('data') ?? [] as $item) {
            $ccy = strtoupper((string) ($item['ccy'] ?? ''));
            if ($ccy === '') {
                continue;
            }
            $byCcy[$ccy] = $item;
        }

        return [
            'base' => $this->normalizeBalance($baseSymbol, $byCcy[$baseSymbol] ?? null),
            'usdt' => $this->normalizeBalance('USDT', $byCcy['USDT'] ?? null),
        ];
    }

    public function cancelOrder(string $market, int|string $orderId): array
    {
        $payload = [
            'market' => $market,
            'market_type' => 'SPOT',
            'order_id' => $orderId,
        ];

        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/cancel-order', $payload);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if (!$response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->error('Coinex cancel order failed', [
                'payload' => $payload,
                'body' => $response->body(),
            ]);

            $message = $response->json('message') ?: 'خطا در لغو سفارش از CoinEx';
            $mapped = CoinexError::tryFrom((int) $response->json('code'));
            if ($mapped) {
                $message = CoinexError::mapErrorToResponse($mapped);
            }

            throw new CantResolveCoinexException($message, (int) $response->json('code'));
        }

        return $response->json('data') ?? [];
    }

    public function getOrderStatus(string $market, int|string $orderId): array
    {
        $order = $this->fetchOrderStatus($market, $orderId);

        if ($order === null) {
            throw new CantResolveCoinexException('سفارشی با این شناسه در بازار '.$market.' یافت نشد.');
        }

        return $order;
    }

    /**
     * @param  list<string>  $markets
     * @return array{market: string, order: array}
     */
    public function findOrderById(int|string $orderId, ?string $market = null, array $markets = []): array
    {
        $targets = $market !== null && $market !== ''
            ? [strtoupper($market)]
            : array_values(array_unique(array_map('strtoupper', $markets)));

        if ($targets === []) {
            throw new CantResolveCoinexException('بازاری برای جستجوی سفارش مشخص نشده است.');
        }

        $lastError = null;

        foreach ($targets as $targetMarket) {
            try {
                $order = $this->fetchOrderStatus($targetMarket, $orderId);
            } catch (CantResolveCoinexException $e) {
                $lastError = $e;
                continue;
            }

            if ($order !== null) {
                return [
                    'market' => $targetMarket,
                    'order' => $order,
                ];
            }
        }

        throw $lastError ?? new CantResolveCoinexException('سفارشی با این شناسه یافت نشد.');
    }

    public function getOrderDeals(string $market, int|string $orderId, int $page = 1, int $limit = 100): array
    {
        $query = [
            'market' => $market,
            'market_type' => 'SPOT',
            'order_id' => $orderId,
            'page' => $page,
            'limit' => $limit,
        ];

        $response = $this->sendGet(
            '/v2/spot/order-deals',
            $query,
            'Coinex order deals failed',
            'خطا در دریافت معاملات سفارش از CoinEx'
        );

        return [
            'data' => $response->json('data') ?? [],
            'pagination' => $response->json('pagination') ?? [
                'has_next' => false,
            ],
        ];
    }

    private function sendGet(string $path, array $query, string $logMessage, string $fallbackMessage): \Illuminate\Http\Client\Response
    {
        try {
            $response = CoinexRequest::send(MethodEnum::GET, $path, $query);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if (!$response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->error($logMessage, [
                'path' => $path,
                'query' => $query,
                'body' => $response->body(),
            ]);

            $message = $response->json('message') ?: $fallbackMessage;
            $mapped = CoinexError::tryFrom((int) $response->json('code'));
            if ($mapped) {
                $message = CoinexError::mapErrorToResponse($mapped);
            }

            throw new CantResolveCoinexException($message, (int) $response->json('code'));
        }

        return $response;
    }

    private function fetchOrderStatus(string $market, int|string $orderId): ?array
    {
        $query = [
            'market' => $market,
            'order_id' => $orderId,
        ];

        try {
            $response = CoinexRequest::send(MethodEnum::GET, '/v2/spot/order-status', $query);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($response->ok() && $response->json('code') === 0) {
            $data = $response->json('data') ?? [];

            return isset($data['order_id']) ? $data : null;
        }

        $code = (int) $response->json('code');
        $message = $response->json('message') ?: 'خطا در دریافت وضعیت سفارش از CoinEx';

        Log::channel('ref-exchange')->warning('Coinex order status lookup miss', [
            'market' => $market,
            'order_id' => $orderId,
            'code' => $code,
            'body' => $response->body(),
        ]);

        if (in_array($code, [4004, 3639], true)) {
            return null;
        }

        $mapped = CoinexError::tryFrom($code);
        if ($mapped) {
            $message = CoinexError::mapErrorToResponse($mapped);
        }

        throw new CantResolveCoinexException($message, $code);
    }

    private function normalizeBalance(string $ccy, ?array $item): array
    {
        $available = (string) ($item['available'] ?? '0');
        $frozen = (string) ($item['frozen'] ?? '0');

        return [
            'ccy' => $ccy,
            'available' => $available,
            'frozen' => $frozen,
            'total' => bcadd($available, $frozen, 8),
        ];
    }

    private function listOrders(string $path, string $market, ?string $side, int $page, int $limit): array
    {
        $query = [
            'market' => $market,
            'market_type' => 'SPOT',
            'page' => $page,
            'limit' => $limit,
        ];

        if ($side !== null && $side !== '') {
            $query['side'] = $side;
        }

        try {
            $response = CoinexRequest::send(MethodEnum::GET, $path, $query);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if (!$response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->error('Coinex list orders failed', [
                'path' => $path,
                'query' => $query,
                'body' => $response->body(),
            ]);

            $message = $response->json('message') ?: 'خطا در دریافت سفارش‌ها از CoinEx';
            $mapped = CoinexError::tryFrom((int) $response->json('code'));
            if ($mapped) {
                $message = CoinexError::mapErrorToResponse($mapped);
            }

            throw new CantResolveCoinexException($message, (int) $response->json('code'));
        }

        return [
            'data' => $response->json('data') ?? [],
            'pagination' => $response->json('pagination') ?? [
                'total' => 0,
                'has_next' => false,
            ],
        ];
    }
}
