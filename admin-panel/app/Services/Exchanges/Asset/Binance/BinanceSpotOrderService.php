<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Exceptions\Exchange\RefExchangeRequestException;
use App\Services\Exchanges\Asset\Contract\SpotOrderServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BinanceSpotOrderService implements SpotOrderServiceInterface
{
    private const OPEN_STATUSES = ['NEW', 'PENDING_NEW', 'PARTIALLY_FILLED'];

    /** Binance order statuses mapped onto the CoinEx status names the views understand. */
    private const STATUS_MAP = [
        'NEW' => 'open',
        'PENDING_NEW' => 'open',
        'PARTIALLY_FILLED' => 'part_deal',
        'FILLED' => 'filled',
        'CANCELED' => 'canceled',
        'PENDING_CANCEL' => 'canceled',
        'EXPIRED' => 'expired',
        'EXPIRED_IN_MATCH' => 'expired',
        'REJECTED' => 'rejected',
    ];

    /** -2013: order does not exist, -1121: symbol not listed on Binance. */
    private const ORDER_MISS_CODES = [-2013, -1121];

    public function getPendingOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array
    {
         $symbol = BinanceOrderFormatter::normalizeSymbol($market);
        $orders = $this->send('GET', '/api/v3/openOrders', [
            'symbol' => $symbol,
        ], 'خطا در دریافت سفارش‌های باز از Binance');

        $result = $this->paginate($orders, $side, $page, $limit);
        $result['data'] = $this->attachFees($symbol, $result['data']);

        return $result;
    }

    public function getFinishedOrders(string $market, ?string $side = null, int $page = 1, int $limit = 50): array
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($market);

        // Binance has no "finished orders" endpoint; allOrders returns the latest 1000 of every status.
        $orders = $this->send('GET', '/api/v3/allOrders', [
            'symbol' => $symbol,
            'limit' => 1000,
        ], 'خطا در دریافت سفارش‌های تکمیل‌شده از Binance');

        $finished = array_filter(
            $orders,
            fn (array $order) => !in_array($order['status'] ?? '', self::OPEN_STATUSES, true)
        );

        $result = $this->paginate($finished, $side, $page, $limit);
        $result['data'] = $this->attachFees($symbol, $result['data']);

        return $result;
    }

    public function getMarketBalances(string $baseSymbol): array
    {
        $baseSymbol = strtoupper($baseSymbol);
        $account = $this->send('GET', '/api/v3/account', [], 'خطا در دریافت موجودی از Binance');

        $byAsset = [];
        foreach ($account['balances'] ?? [] as $item) {
            $byAsset[strtoupper((string) ($item['asset'] ?? ''))] = $item;
        }

        return [
            'base' => $this->normalizeBalance($baseSymbol, $byAsset[$baseSymbol] ?? null),
            'usdt' => $this->normalizeBalance('USDT', $byAsset['USDT'] ?? null),
        ];
    }

    public function cancelOrder(string $market, int|string $orderId): array
    {
        $order = $this->send('DELETE', '/api/v3/order', [
            'symbol' => BinanceOrderFormatter::normalizeSymbol($market),
            'orderId' => $orderId,
        ], 'خطا در لغو سفارش از Binance');

        return $this->normalizeOrder($order);
    }

    public function findOrderById(int|string $orderId, ?string $market = null, array $markets = []): array
    {
        $targets = $market !== null && $market !== ''
            ? [BinanceOrderFormatter::normalizeSymbol($market)]
            : array_values(array_unique(array_map([BinanceOrderFormatter::class, 'normalizeSymbol'], $markets)));

        if ($targets === []) {
            throw new RefExchangeRequestException('بازاری برای جستجوی سفارش مشخص نشده است.');
        }

        foreach ($targets as $targetMarket) {
            $order = $this->fetchOrder($targetMarket, $orderId);

            if ($order !== null) {
                return [
                    'market' => $targetMarket,
                    'order' => $order,
                ];
            }
        }

        throw new RefExchangeRequestException('سفارشی با این شناسه یافت نشد.');
    }

    public function getOrderDeals(string $market, int|string $orderId, int $page = 1, int $limit = 100): array
    {
        $trades = $this->send('GET', '/api/v3/myTrades', [
            'symbol' => BinanceOrderFormatter::normalizeSymbol($market),
            'orderId' => $orderId,
        ], 'خطا در دریافت معاملات سفارش از Binance');

        $deals = array_map(fn (array $trade) => [
            'deal_id' => $trade['id'] ?? null,
            'order_id' => $trade['orderId'] ?? null,
            'market' => $trade['symbol'] ?? null,
            'side' => !empty($trade['isBuyer']) ? 'buy' : 'sell',
            'price' => BinanceOrderFormatter::plainDecimal((string) ($trade['price'] ?? '0')),
            'amount' => BinanceOrderFormatter::plainDecimal((string) ($trade['qty'] ?? '0')),
            'role' => !empty($trade['isMaker']) ? 'maker' : 'taker',
            'fee' => BinanceOrderFormatter::plainDecimal((string) ($trade['commission'] ?? '0')),
            'fee_ccy' => $trade['commissionAsset'] ?? null,
            'created_at' => $trade['time'] ?? null,
        ], $trades);

        $offset = (max(1, $page) - 1) * $limit;

        return [
            'data' => array_slice($deals, $offset, $limit),
            'pagination' => ['has_next' => count($deals) > $offset + $limit],
        ];
    }

    private function fetchOrder(string $symbol, int|string $orderId): ?array
    {
        try {
            $order = $this->send('GET', '/api/v3/order', [
                'symbol' => $symbol,
                'orderId' => $orderId,
            ], 'خطا در دریافت وضعیت سفارش از Binance');
        } catch (RefExchangeRequestException $e) {
            if (in_array($e->getCode(), self::ORDER_MISS_CODES, true)) {
                return null;
            }

            throw $e;
        }

        return isset($order['orderId']) ? $this->normalizeOrder($order) : null;
    }

    private function send(string $method, string $path, array $params, string $fallbackMessage): array
    {
        try {
            $response = BinanceRequest::sendRequest($method, $path, $params);
        } catch (Throwable $exception) {
            report($exception);

            // Keep only the cURL reason; the rest of the message is the signed request URL.
            throw new RefExchangeRequestException(Str::before($exception->getMessage(), ' (see '), 0, $exception);
        }

        $json = $response->json();

        if (!$response->ok() || !is_array($json) || isset($json['code'])) {
            Log::channel('ref-exchange')->error('Binance spot order request failed', [
                'method' => $method,
                'path' => $path,
                'params' => $params,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RefExchangeRequestException(
                (string) (is_array($json) ? ($json['msg'] ?? $fallbackMessage) : $fallbackMessage),
                is_array($json) ? (int) ($json['code'] ?? 0) : 0
            );
        }

        return $json;
    }

    private function paginate(array $orders, ?string $side, int $page, int $limit): array
    {
        $orders = array_map(fn (array $order) => $this->normalizeOrder($order), array_values($orders));

        if ($side !== null && $side !== '') {
            $orders = array_values(array_filter($orders, fn (array $order) => $order['side'] === strtolower($side)));
        }

        usort($orders, fn (array $a, array $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));

        $offset = (max(1, $page) - 1) * $limit;

        return [
            'data' => array_slice($orders, $offset, $limit),
            'pagination' => [
                'total' => count($orders),
                'has_next' => count($orders) > $offset + $limit,
            ],
        ];
    }

    /**
     * Order endpoints carry no commission, so it is summed from the account's latest
     * 1000 trades of the market. Orders older than that window keep no fee keys.
     */
    private function attachFees(string $symbol, array $orders): array
    {
        $hasFills = array_filter($orders, fn (array $order) => bccomp($order['filled_amount'], '0', 8) === 1);
        if ($hasFills === []) {
            return $orders;
        }

        try {
            $trades = $this->send('GET', '/api/v3/myTrades', [
                'symbol' => $symbol,
                'limit' => 1000,
            ], 'خطا در دریافت معاملات از Binance');
        } catch (RefExchangeRequestException) {
            return $orders;
        }

        $fees = [];
        foreach ($trades as $trade) {
            $asset = strtoupper((string) ($trade['commissionAsset'] ?? ''));
            if ($asset === '') {
                continue;
            }

            $key = match (true) {
                str_starts_with($symbol, $asset) => 'base_fee',
                str_ends_with($symbol, $asset) => 'quote_fee',
                default => 'discount_fee',
            };
            $orderId = (string) ($trade['orderId'] ?? '');
            $fees[$orderId][$key] = bcadd(
                $fees[$orderId][$key] ?? '0',
                BinanceOrderFormatter::plainDecimal((string) ($trade['commission'] ?? '0')),
                8
            );
        }

        return array_map(function (array $order) use ($fees) {
            $orderFees = $fees[(string) $order['order_id']] ?? null;
            if ($orderFees === null) {
                return $order;
            }

            return $order + [
                'base_fee' => BinanceOrderFormatter::plainDecimal($orderFees['base_fee'] ?? '0'),
                'quote_fee' => BinanceOrderFormatter::plainDecimal($orderFees['quote_fee'] ?? '0'),
                'discount_fee' => BinanceOrderFormatter::plainDecimal($orderFees['discount_fee'] ?? '0'),
            ];
        }, $orders);
    }

    private function normalizeOrder(array $order): array
    {
        $amount = BinanceOrderFormatter::plainDecimal((string) ($order['origQty'] ?? '0'));
        $filled = BinanceOrderFormatter::plainDecimal((string) ($order['executedQty'] ?? '0'));
        $status = strtoupper((string) ($order['status'] ?? ''));

        return [
            'order_id' => $order['orderId'] ?? null,
            'market' => $order['symbol'] ?? null,
            'side' => strtolower((string) ($order['side'] ?? '')),
            'type' => strtolower((string) ($order['type'] ?? '')),
            'amount' => $amount,
            'price' => BinanceOrderFormatter::plainDecimal((string) ($order['price'] ?? '0')),
            'unfilled_amount' => BinanceOrderFormatter::plainDecimal(bcsub($amount, $filled, 8)),
            'filled_amount' => $filled,
            'filled_value' => BinanceOrderFormatter::plainDecimal((string) ($order['cummulativeQuoteQty'] ?? '0')),
            'client_id' => $order['clientOrderId'] ?? null,
            'created_at' => $order['time'] ?? $order['transactTime'] ?? null,
            'updated_at' => $order['updateTime'] ?? $order['transactTime'] ?? null,
            'status' => self::STATUS_MAP[$status] ?? strtolower($status),
        ];
    }

    private function normalizeBalance(string $ccy, ?array $item): array
    {
        $available = BinanceOrderFormatter::plainDecimal((string) ($item['free'] ?? '0'));
        $frozen = BinanceOrderFormatter::plainDecimal((string) ($item['locked'] ?? '0'));

        return [
            'ccy' => $ccy,
            'available' => $available,
            'frozen' => $frozen,
            'total' => bcadd($available, $frozen, 8),
        ];
    }
}
