<?php

namespace App\Services\Bot\ReferenceExchange;

use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;
use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Binance implementation of the reference exchange used by the auto-trade bot.
 *
 * Uses the same omnibus API key as the OTC / Spot integration (`BinanceRequest`).
 *
 * `GET /api/v3/order` does not report commissions, so for executed orders the
 * fee is read from `/api/v3/myTrades`. `exchangeFee` is always expressed in the
 * quote asset (USDT), like the CoinEx adapter.
 */
class BinanceBotAdapter implements ExchangeContract
{
    private const SCALE = 8;
    private const QUOTE = 'USDT';

    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult
    {
        return $this->placeOrder($market, [
            'side'          => 'BUY',
            'type'          => 'MARKET',
            'quoteOrderQty' => BinanceOrderFormatter::formatQuantity($quoteAmount, null, self::SCALE),
        ]);
    }

    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult
    {
        return $this->placeOrder($market, [
            'side'        => 'SELL',
            'type'        => 'LIMIT',
            'timeInForce' => 'GTC',
        ], $baseAmount, $price);
    }

    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult
    {
        return $this->placeOrder($market, [
            'side' => 'SELL',
            'type' => 'MARKET',
        ], $baseAmount);
    }

    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($market);
        $ctx    = ['op' => 'get_order', 'market' => $symbol, 'order_id' => $exchangeOrderId];

        try {
            $response = BinanceRequest::sendRequest('GET', '/api/v3/order', [
                'symbol'  => $symbol,
                'orderId' => $exchangeOrderId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('binance.bot.get_order.transport_error', $ctx + ['error' => $e->getMessage()]);

            return $this->failed('TRANSPORT', $e->getMessage());
        }

        $order = $this->decode($response, $ctx);
        if ($order instanceof ExchangeOrderResult) {
            return $order;
        }

        $executed = BinanceOrderFormatter::plainDecimal((string) ($order['executedQty'] ?? '0'));
        $fills    = bccomp($executed, '0', self::SCALE) === 1 ? $this->trades($symbol, $exchangeOrderId) : [];

        return $this->toResult($order, $fills);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($market);
        $ctx    = ['op' => 'cancel', 'market' => $symbol, 'order_id' => $exchangeOrderId];

        Log::channel('smart-bot')->info('binance.bot.cancel.request', $ctx);

        try {
            $response = BinanceRequest::sendRequest('DELETE', '/api/v3/order', [
                'symbol'  => $symbol,
                'orderId' => $exchangeOrderId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('binance.bot.cancel.transport_error', $ctx + ['error' => $e->getMessage()]);
            throw new RuntimeException('binance.cancel.transport_error: '.$e->getMessage(), 0, $e);
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $code = (int) ($body['code'] ?? 0);

        // -2011 "Unknown order sent." = already filled / canceled — the order is effectively gone.
        if (($response->successful() && ! isset($body['code'])) || $code === -2011) {
            Log::channel('smart-bot')->info('binance.bot.cancel.ok', $ctx + ['code' => $code]);
            return;
        }

        Log::channel('smart-bot')->warning('binance.bot.cancel.failed', $ctx + [
            'binance_code'    => $code,
            'binance_message' => $body['msg'] ?? null,
            'http_status'     => $response->status(),
            'raw'             => $body ?: $response->body(),
        ]);

        throw new RuntimeException('binance.cancel.api_error: '.($body['msg'] ?? 'http '.$response->status()));
    }

    public function name(): string
    {
        return 'binance';
    }

    /* ---------------------------------------------------------------------- */

    private function placeOrder(string $market, array $params, ?string $baseAmount = null, ?string $price = null): ExchangeOrderResult
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($market);
        $ctx    = [
            'op'     => 'place',
            'market' => $symbol,
            'side'   => $params['side'],
            'type'   => $params['type'],
            'amount' => $baseAmount ?? ($params['quoteOrderQty'] ?? null),
            'price'  => $price,
        ];

        try {
            if ($baseAmount !== null) {
                $filters  = BinanceRequest::symbolFilters($symbol);
                $quantity = BinanceOrderFormatter::formatQuantity(
                    $baseAmount,
                    BinanceOrderFormatter::stepFor($params['type'], $filters),
                );
                $minQty = BinanceOrderFormatter::minQtyFor($params['type'], $filters) ?? '0';

                if (bccomp($quantity, '0', 18) !== 1 || bccomp($quantity, $minQty, 18) === -1) {
                    Log::channel('smart-bot')->error('binance.bot.place.amount_too_small', $ctx + [
                        'quantity' => $quantity,
                        'min_qty'  => $minQty,
                    ]);

                    return $this->failed('AMOUNT_TOO_SMALL', "quantity {$quantity} is below Binance minimum {$minQty}");
                }

                $params['quantity'] = $quantity;

                if ($price !== null) {
                    $params['price'] = BinanceOrderFormatter::formatQuantity($price, $filters['tickSize'] ?? null);
                }
            }

            $response = BinanceRequest::sendRequest('POST', '/api/v3/order', [
                'symbol'           => $symbol,
                'newOrderRespType' => 'FULL',
            ] + $params);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('binance.bot.place.transport_error', $ctx + ['error' => $e->getMessage()]);

            return $this->failed('TRANSPORT', $e->getMessage());
        }

        $order = $this->decode($response, $ctx);
        if ($order instanceof ExchangeOrderResult) {
            return $order;
        }

        return $this->toResult($order, is_array($order['fills'] ?? null) ? $order['fills'] : []);
    }

    /**
     * @return array<string, mixed>|ExchangeOrderResult the order payload, or a FAILED / NOT_FOUND result
     */
    private function decode(Response $response, array $ctx): array|ExchangeOrderResult
    {
        $op   = $ctx['op'];
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if (! $response->successful() || isset($body['code'])) {
            $code = (int) ($body['code'] ?? 0);

            Log::channel('smart-bot')->error("binance.bot.{$op}.failed", $ctx + [
                'binance_code'    => $code,
                'binance_message' => $body['msg'] ?? null,
                'http_status'     => $response->status(),
                'raw'             => $body ?: $response->body(),
            ]);

            // -2013 "Order does not exist."
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          $code === -2013 ? ExchangeOrderStatus::NOT_FOUND : ExchangeOrderStatus::FAILED,
                errorCode:       (string) ($code ?: $response->status()),
                errorMessage:    (string) ($body['msg'] ?? 'unknown Binance error'),
            );
        }

        if (! isset($body['orderId'])) {
            Log::channel('smart-bot')->error("binance.bot.{$op}.empty_data", $ctx + [
                'http_status' => $response->status(),
                'raw'         => $body,
            ]);

            return $this->failed('EMPTY_DATA', 'Binance returned success without an order');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<int, array<string, mixed>>  $fills  order `fills` or myTrades rows (both carry commission / commissionAsset)
     */
    private function toResult(array $order, array $fills): ExchangeOrderResult
    {
        $base     = $this->baseOf((string) ($order['symbol'] ?? ''));
        $isBuy    = strtoupper((string) ($order['side'] ?? '')) === 'BUY';
        $executed = BinanceOrderFormatter::plainDecimal((string) ($order['executedQty'] ?? '0'));
        $quoteQty = BinanceOrderFormatter::plainDecimal((string) ($order['cummulativeQuoteQty'] ?? '0'));

        $avgPrice = bccomp($executed, '0', self::SCALE) === 1
            ? bcdiv($quoteQty, $executed, self::SCALE)
            : BinanceOrderFormatter::plainDecimal((string) ($order['price'] ?? '0'));

        $commission = BinanceOrderFormatter::commission($fills, $isBuy ? [$base, self::QUOTE] : [self::QUOTE, $base]);

        // A buy fee charged in the base coin is deducted from the received coin.
        $filled = $executed;
        if (
            $isBuy
            && $commission['currency'] === $base
            && bccomp($commission['amount'], '0', self::SCALE) === 1
            && bccomp($executed, $commission['amount'], self::SCALE) >= 0
        ) {
            $filled = bcsub($executed, $commission['amount'], self::SCALE);
        }

        return new ExchangeOrderResult(
            exchangeOrderId: (string) $order['orderId'],
            status:          $this->mapStatus(
                (string) ($order['status'] ?? ''),
                (string) ($order['type'] ?? ''),
                $executed,
            ),
            filledAmount:    $filled,
            avgPrice:        $avgPrice,
            exchangeFee:     $this->feeInQuote($commission['amount'], $commission['currency'], $base, $avgPrice),
            feeCurrency:     $commission['currency'],
        );
    }

    private function mapStatus(string $status, string $type, string $executed): ExchangeOrderStatus
    {
        return match (strtoupper($status)) {
            'FILLED'           => ExchangeOrderStatus::FILLED,
            'PARTIALLY_FILLED' => ExchangeOrderStatus::PARTIAL,
            // A MARKET order that runs out of liquidity expires with whatever it already filled.
            'CANCELED', 'REJECTED', 'EXPIRED', 'EXPIRED_IN_MATCH' => strtoupper($type) === 'MARKET' && bccomp($executed, '0', self::SCALE) === 1
                ? ExchangeOrderStatus::PARTIAL
                : ExchangeOrderStatus::CANCELED,
            default            => ExchangeOrderStatus::OPEN,
        };
    }

    private function feeInQuote(string $amount, ?string $asset, string $base, string $avgPrice): string
    {
        if ($asset === null || bccomp($amount, '0', self::SCALE) !== 1) {
            return '0';
        }

        if ($asset === self::QUOTE) {
            return bcadd($amount, '0', self::SCALE);
        }

        if ($asset === $base) {
            return bcmul($amount, $avgPrice, self::SCALE);
        }

        // Fee paid in a third asset (BNB when "pay fees with BNB" is enabled).
        try {
            $price = (string) Http::timeout(10)
                ->get(config('exchanges.binance.base_url').'/api/v3/ticker/price', ['symbol' => $asset.self::QUOTE])
                ->json('price');

            return bcmul($amount, BinanceOrderFormatter::plainDecimal($price), self::SCALE);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('binance.bot.fee_conversion_failed', [
                'asset'  => $asset,
                'amount' => $amount,
                'error'  => $e->getMessage(),
            ]);

            return '0';
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function trades(string $symbol, string $orderId): array
    {
        $response = BinanceRequest::sendRequest('GET', '/api/v3/myTrades', [
            'symbol'  => $symbol,
            'orderId' => $orderId,
        ]);

        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || isset($body['code'])) {
            throw new RuntimeException('binance.my_trades.failed: '.$response->body());
        }

        return $body;
    }

    private function baseOf(string $symbol): string
    {
        $symbol = strtoupper($symbol);

        return str_ends_with($symbol, self::QUOTE) ? substr($symbol, 0, -strlen(self::QUOTE)) : $symbol;
    }

    private function failed(string $code, string $message): ExchangeOrderResult
    {
        return new ExchangeOrderResult(
            exchangeOrderId: null,
            status:          ExchangeOrderStatus::FAILED,
            errorCode:       $code,
            errorMessage:    $message,
        );
    }
}
