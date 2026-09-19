<?php

namespace App\Services\Bot\ReferenceExchange;

use App\Services\Exchanges\Asset\Coinex\Authentication\CoinexAuthentication;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Coinex\CoinexRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * CoinEx implementation of the reference exchange used by the auto-trade bot.
 *
 * Uses the existing shared `CoinexRequest` / `CoinexAuthentication` helpers that
 * already power the OTC and Spot integrations (single omnibus account).
 *
 * GET requests need a small workaround because the shared signer JSON-encodes the
 * data array for every method; we therefore pre-build the query string into the
 * path and pass an empty data array to the signer.
 */
class CoinExBotAdapter implements ExchangeContract
{
    private const MARKET_TYPE = 'SPOT';

    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult
    {
        return $this->placeOrder([
            'market'      => $market,
            'market_type' => self::MARKET_TYPE,
            'side'        => 'buy',
            'type'        => 'market',
            'amount'      => $quoteAmount,
            'ccy'         => $this->quoteOf($market),
        ]);
    }

    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult
    {
        return $this->placeOrder([
            'market'      => $market,
            'market_type' => self::MARKET_TYPE,
            'side'        => 'sell',
            'type'        => 'limit',
            'amount'      => $baseAmount,
            'price'       => $price,
        ]);
    }

    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult
    {
        return $this->placeOrder([
            'market'      => $market,
            'market_type' => self::MARKET_TYPE,
            'side'        => 'sell',
            'type'        => 'market',
            'amount'      => $baseAmount,
        ]);
    }

    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult
    {
        $ctx = $this->diagContext([
            'op'       => 'get_order',
            'market'   => $market,
            'order_id' => $exchangeOrderId,
        ]);

        $query = http_build_query([
            'market'   => $market,
            'order_id' => $exchangeOrderId,
        ]);
        $pathWithQuery = '/v2/spot/order-status?'.$query;


        try {
            $response = $this->signedGet($pathWithQuery);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('coinex.bot.get_order.transport_error', $ctx + [
                'error' => $e->getMessage(),
            ]);

            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'TRANSPORT',
                errorMessage:    $e->getMessage(),
            );
        }

        return $this->parseOrderResponse($response, $ctx);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        $ctx = $this->diagContext([
            'op'       => 'cancel',
            'market'   => $market,
            'order_id' => $exchangeOrderId,
        ]);

        Log::channel('smart-bot')->info('coinex.bot.cancel.request', $ctx);

        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/cancel-order', [
                'market'      => $market,
                'market_type' => self::MARKET_TYPE,
                'order_id'    => (int) $exchangeOrderId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('coinex.bot.cancel.transport_error', $ctx + [
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('coinex.cancel.transport_error: '.$e->getMessage(), 0, $e);
        }

        $body = $response->json() ?: [];
        $code = (int) ($body['code'] ?? -1);

        // 0 = success; CoinEx error codes 3127 (order not found) / 3606 (already finished)
        // are treated as soft success — the order is effectively gone.
        if ($code === 0 || $code === 3127 || $code === 3606) {
            Log::channel('smart-bot')->info('coinex.bot.cancel.ok', $ctx + ['code' => $code]);
            return;
        }

        Log::channel('smart-bot')->warning('coinex.bot.cancel.failed', $ctx + [
            'coinex_code'    => $code,
            'coinex_message' => $body['message'] ?? null,
            'http_status'    => $response->status(),
            'raw'            => $body,
        ]);

        throw new RuntimeException('coinex.cancel.api_error: '.($body['message'] ?? "code {$code}"));
    }

    /* ---------------------------------------------------------------------- */

    private function placeOrder(array $payload): ExchangeOrderResult
    {
        $ctx = $this->diagContext([
            'op'     => 'place',
            'market' => $payload['market'] ?? null,
            'side'   => $payload['side'] ?? null,
            'type'   => $payload['type'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'price'  => $payload['price'] ?? null,
            'ccy'    => $payload['ccy'] ?? null,
        ]);

        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/order', $payload);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('coinex.bot.place.transport_error', $ctx + [
                'error' => $e->getMessage(),
            ]);

            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'TRANSPORT',
                errorMessage:    $e->getMessage(),
            );
        }

        return $this->parseOrderResponse($response, $ctx);
    }

    /**
     * Diagnostic fields shared by every CoinEx bot call so we can tell which
     * worker / API key handled a given request (e.g. intermittent code 158).
     * Access ID is logged in full (public half of the key pair); secret is never logged.
     */
    private function diagContext(array $extra = []): array
    {
        $accessId = (string) config('exchanges.coinex.access_id');

        return array_merge([
            'access_id'      => $accessId !== '' ? $accessId : null,
            'access_id_tail' => $accessId !== '' ? substr($accessId, -8) : null,
            'base_url'       => config('exchanges.coinex.base_url_v2'),
            'host'           => gethostname() ?: null,
            'pid'            => getmypid() ?: null,
        ], $extra);
    }

    private function signedGet(string $pathWithQuery): Response
    {
        $timestamp = (int) round(microtime(true) * 1000);
        $sign      = CoinexAuthentication::getSigned(MethodEnum::GET, $pathWithQuery, $timestamp, []);

        return Http::baseUrl(config('exchanges.coinex.base_url_v2'))
            ->withHeaders([
                'X-COINEX-KEY'       => config('exchanges.coinex.access_id'),
                'X-COINEX-SIGN'      => $sign,
                'X-COINEX-TIMESTAMP' => $timestamp,
            ])
            ->get($pathWithQuery);
    }

    /**
     * Parse a CoinEx order/order-status response into an ExchangeOrderResult
     * and emit a single authoritative log line for the call. The raw CoinEx
     * body (code + message + data) is always logged on failure so an
     * intermittent permission error (code 158) can never disappear silently.
     */
    private function parseOrderResponse(Response $response, array $ctx = []): ExchangeOrderResult
    {
        $op   = $ctx['op'] ?? 'order';
        $body = $response->json() ?: [];
        $code = (int) ($body['code'] ?? -1);

        if ($code !== 0) {
            Log::channel('smart-bot')->error("coinex.bot.{$op}.failed", $ctx + [
                'coinex_code'    => $code,
                'coinex_message' => $body['message'] ?? null,
                'http_status'    => $response->status(),
                'raw'            => $body,
            ]);

            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       (string) $code,
                errorMessage:    (string) ($body['message'] ?? 'unknown CoinEx error'),
            );
        }

        $data = $body['data'] ?? [];
        if (! is_array($data) || empty($data)) {
            Log::channel('smart-bot')->error("coinex.bot.{$op}.empty_data", $ctx + [
                'http_status' => $response->status(),
                'raw'         => $body,
            ]);

            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'EMPTY_DATA',
                errorMessage:    'CoinEx returned success with empty data',
            );
        }

        $orderId  = isset($data['order_id']) ? (string) $data['order_id'] : null;
        $status   = $this->mapStatus((string) ($data['status'] ?? ''));
        $avgPrice = $this->resolveAvgPrice($data);
        $side     = isset($data['side']) ? (string) $data['side'] : ($ctx['side'] ?? null);
        $market   = (string) ($data['market'] ?? $ctx['market'] ?? '');
        [$baseSymbol, $quoteSymbol] = $this->splitMarket($market);
        $fill = CoinExFillResolver::resolve(
            $data,
            $avgPrice,
            $side !== null ? (string) $side : null,
            $baseSymbol,
            $quoteSymbol,
        );

        // Log::channel('smart-bot')->info("coinex.bot.{$op}.ok", $ctx + [
        //     'exchange_order_id' => $orderId,
        //     'status'            => $status->value,
        //     'raw_status'        => $data['status'] ?? null,
        //     'side'              => $side,
        //     'market'            => $market !== '' ? $market : null,
        //     'gross_filled'      => $fill['gross_filled'],
        //     'base_fee'          => $fill['base_fee'],
        //     'quote_fee'         => $fill['quote_fee'],
        //     'filled_amount'     => $fill['filled_amount'],
        //     'fee_currency'      => $fill['fee_currency'],
        //     'exchange_fee'      => $fill['exchange_fee'],
        //     'avg_price'         => $avgPrice,
        // ]);

        return new ExchangeOrderResult(
            exchangeOrderId: $orderId,
            status:          $status,
            filledAmount:    $fill['filled_amount'],
            avgPrice:        $avgPrice,
            exchangeFee:     $fill['exchange_fee'],
            feeCurrency:     $fill['fee_currency'],
        );
    }

    private function mapStatus(string $raw): ExchangeOrderStatus
    {
        return match (strtolower($raw)) {
            'done', 'finished', 'filled'                => ExchangeOrderStatus::FILLED,
            'part_deal', 'partially_filled'             => ExchangeOrderStatus::PARTIAL,
            'open', 'pending', 'not_deal', 'unfinished' => ExchangeOrderStatus::OPEN,
            'cancel', 'canceled', 'cancelled'           => ExchangeOrderStatus::CANCELED,
            default                                     => ExchangeOrderStatus::OPEN,
        };
    }

    private function resolveAvgPrice(array $data): string
    {
        if (isset($data['avg_price']) && (string) $data['avg_price'] !== '0') {
            return (string) $data['avg_price'];
        }
        $filled = (string) ($data['filled_amount'] ?? '0');
        $value  = (string) ($data['filled_value'] ?? '0');
        if (bccomp($filled, '0', 8) > 0) {
            return bcdiv($value, $filled, 8);
        }
        return (string) ($data['price'] ?? '0');
    }

    /**
     * @return array{0: string, 1: string} [baseSymbol, quoteSymbol]
     */
    private function splitMarket(string $market): array
    {
        $market = strtoupper($market);
        $quote  = $this->quoteOf($market);
        if ($market !== '' && str_ends_with($market, $quote) && strlen($market) > strlen($quote)) {
            return [substr($market, 0, -strlen($quote)), $quote];
        }

        return ['', $quote !== '' ? $quote : 'USDT'];
    }

    private function quoteOf(string $market): string
    {
        // Conventional CoinEx pair: BASE+QUOTE concatenated. Bot trades against USDT.
        return str_ends_with($market, 'USDT') ? 'USDT' : (strlen($market) >= 3 ? substr($market, -3) : 'USDT');
    }
}
