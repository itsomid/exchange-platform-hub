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

    public function cancelOrder(string $market, int|string $orderId): array
    {
        $payload = [
            'market' => $market,
            'market_type' => 'SPOT',
            'order_id' => (int) $orderId,
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
