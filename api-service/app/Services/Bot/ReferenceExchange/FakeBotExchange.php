<?php

namespace App\Services\Bot\ReferenceExchange;

use App\Models\Market;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * In-process fake of {@see ExchangeContract} used by the admin Test Lab
 * harness. Behaves like CoinEx but never talks to the network:
 *
 *   - placeMarketBuy: prices the buy off the current `exchange_prices` row
 *     for the matching USDT market, returns FILLED immediately.
 *   - placeLimitSell: records the order in cache with status OPEN.
 *   - getOrder: looks at the current price for the market and returns FILLED
 *     when price >= limit (so the existing `bot:sync-sell-orders` poller
 *     drives settlement naturally). Returns CANCELED if cancelOrder was
 *     called on it.
 *
 * All fake orders are stored under the `bot:fake_ex:` cache prefix so the
 * Test Lab reset can purge them. Selected by `config('smart-bot.exchange_driver')`
 * = `fake`.
 */
class FakeBotExchange implements ExchangeContract
{
    private const CACHE_PREFIX = 'bot:fake_ex:';
    private const TTL_SECONDS  = 60 * 60 * 24 * 30; // 30 days
    private const SCALE        = 8;
    private const FEE_RATE     = '0.001';            // 0.1% taker — match CoinEx default

    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult
    {
        $price = $this->currentPrice($market);
        if ($price === null) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'FAKE_NO_PRICE',
                errorMessage:    "No exchange_prices row for {$market}",
            );
        }

        $amount = bcdiv($quoteAmount, $price, self::SCALE);
        $fee    = bcmul($quoteAmount, self::FEE_RATE, self::SCALE);
        $id     = $this->nextId('buy');

        Cache::put(self::CACHE_PREFIX.$id, [
            'kind'      => 'buy',
            'market'    => $market,
            'amount'    => $amount,
            'price'     => $price,
            'quote'     => $quoteAmount,
            'status'    => 'FILLED',
            'placed_at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);

        return new ExchangeOrderResult(
            exchangeOrderId: $id,
            status:          ExchangeOrderStatus::FILLED,
            filledAmount:    $amount,
            avgPrice:        $price,
            exchangeFee:     $fee,
            feeCurrency:     'USDT',
        );
    }

    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult
    {
        $id = $this->nextId('sell');

        Cache::put(self::CACHE_PREFIX.$id, [
            'kind'      => 'sell',
            'market'    => $market,
            'amount'    => $baseAmount,
            'price'     => $price,
            'status'    => 'OPEN',
            'placed_at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);

        return new ExchangeOrderResult(
            exchangeOrderId: $id,
            status:          ExchangeOrderStatus::OPEN,
        );
    }

    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult
    {
        $price = $this->currentPrice($market);
        if ($price === null) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'FAKE_NO_PRICE',
                errorMessage:    "No exchange_prices row for {$market}",
            );
        }

        $quote = bcmul($baseAmount, $price, self::SCALE);
        $fee   = bcmul($quote, self::FEE_RATE, self::SCALE);
        $id    = $this->nextId('sellm');

        Cache::put(self::CACHE_PREFIX.$id, [
            'kind'      => 'sell_market',
            'market'    => $market,
            'amount'    => $baseAmount,
            'price'     => $price,
            'quote'     => $quote,
            'status'    => 'FILLED',
            'placed_at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);

        return new ExchangeOrderResult(
            exchangeOrderId: $id,
            status:          ExchangeOrderStatus::FILLED,
            filledAmount:    $baseAmount,
            avgPrice:        $price,
            exchangeFee:     $fee,
        );
    }

    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult
    {
        $key   = self::CACHE_PREFIX.$exchangeOrderId;
        $order = Cache::get($key);

        if (! is_array($order)) {
            return new ExchangeOrderResult($exchangeOrderId, ExchangeOrderStatus::NOT_FOUND);
        }

        if (($order['status'] ?? null) === 'CANCELED') {
            return new ExchangeOrderResult($exchangeOrderId, ExchangeOrderStatus::CANCELED);
        }

        if (($order['status'] ?? null) === 'FILLED') {
            return $this->filledResult($exchangeOrderId, $order);
        }

        // OPEN sell: check current price vs limit
        if (($order['kind'] ?? null) === 'sell') {
            $now = $this->currentPrice($market);
            if ($now !== null && bccomp($now, (string) $order['price'], self::SCALE) >= 0) {
                $order['status']    = 'FILLED';
                $order['filled_at'] = now()->toIso8601String();
                Cache::put($key, $order, self::TTL_SECONDS);
                return $this->filledResult($exchangeOrderId, $order);
            }
        }

        return new ExchangeOrderResult($exchangeOrderId, ExchangeOrderStatus::OPEN);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        $key   = self::CACHE_PREFIX.$exchangeOrderId;
        $order = Cache::get($key);
        if (is_array($order)) {
            $order['status']      = 'CANCELED';
            $order['canceled_at'] = now()->toIso8601String();
            Cache::put($key, $order, self::TTL_SECONDS);
        }
    }

    /**
     * Wipe every fake order this driver has ever placed. Used by the Test
     * Lab reset endpoint to start from a clean slate.
     *
     * @return int number of cache entries removed
     */
    public static function purgeAll(): int
    {
        $count = 0;
        foreach ((array) Cache::get(self::CACHE_PREFIX.'_index', []) as $id) {
            if (Cache::forget(self::CACHE_PREFIX.$id)) {
                $count++;
            }
        }
        Cache::forget(self::CACHE_PREFIX.'_index');
        return $count;
    }

    private function filledResult(string $id, array $order): ExchangeOrderResult
    {
        $gross = bcmul((string) $order['amount'], (string) $order['price'], self::SCALE);
        $fee   = bcmul($gross, self::FEE_RATE, self::SCALE);
        return new ExchangeOrderResult(
            exchangeOrderId: $id,
            status:          ExchangeOrderStatus::FILLED,
            filledAmount:    (string) $order['amount'],
            avgPrice:        (string) $order['price'],
            exchangeFee:     $fee,
        );
    }

    private function currentPrice(string $market): ?string
    {
        // market = "BTCUSDT" → base = BTC, quote = USDT
        $upper = strtoupper($market);
        $base  = str_ends_with($upper, 'USDT') ? substr($upper, 0, -4) : $upper;

        $row = Market::where('base_currency', $base)
            ->where('quote_currency', 'USDT')
            ->with('exchangePrice')
            ->first();

        if (! $row || ! $row->exchangePrice || (float) $row->exchangePrice->price <= 0) {
            return null;
        }
        return (string) $row->exchangePrice->price;
    }

    private function nextId(string $kind): string
    {
        // bot_buy_executions.exchange_order_id is an unsignedBigInteger (real exchange IDs are numeric).
        // Generate a numeric string that fits in BIGINT: timestamp-ms (13 digits) + 4 random digits.
        $id = (string) (now()->getTimestampMs() * 10000 + random_int(0, 9999));

        $index = (array) Cache::get(self::CACHE_PREFIX.'_index', []);
        $index[] = $id;
        Cache::put(self::CACHE_PREFIX.'_index', $index, self::TTL_SECONDS);

        return $id;
    }
}
