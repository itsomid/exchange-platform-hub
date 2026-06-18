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
        $query = http_build_query([
            'market'   => $market,
            'order_id' => $exchangeOrderId,
        ]);
        $pathWithQuery = '/v2/spot/order-status?'.$query;

        $response = $this->signedGet($pathWithQuery);

        return $this->parseOrderResponse($response);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/cancel-order', [
                'market'      => $market,
                'market_type' => self::MARKET_TYPE,
                'order_id'    => (int) $exchangeOrderId,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('coinex.cancel.transport_error: '.$e->getMessage(), 0, $e);
        }

        $body = $response->json() ?: [];
        $code = (int) ($body['code'] ?? -1);

        // 0 = success; CoinEx error codes 3127 (order not found) / 3606 (already finished)
        // are treated as soft success — the order is effectively gone.
        if ($code === 0 || $code === 3127 || $code === 3606) {
            return;
        }

        Log::channel('single')->warning('coinex.cancel.failed', [
            'market'   => $market,
            'order_id' => $exchangeOrderId,
            'code'     => $code,
            'message'  => $body['message'] ?? null,
        ]);

        throw new RuntimeException('coinex.cancel.api_error: '.($body['message'] ?? "code {$code}"));
    }

    /* ---------------------------------------------------------------------- */

    private function placeOrder(array $payload): ExchangeOrderResult
    {
        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/order', $payload);
        } catch (\Throwable $e) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'TRANSPORT',
                errorMessage:    $e->getMessage(),
            );
        }

        return $this->parseOrderResponse($response);
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

    private function parseOrderResponse(Response $response): ExchangeOrderResult
    {
        $body = $response->json() ?: [];
        $code = (int) ($body['code'] ?? -1);

        if ($code !== 0) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       (string) $code,
                errorMessage:    (string) ($body['message'] ?? 'unknown CoinEx error'),
            );
        }

        $data = $body['data'] ?? [];
        if (! is_array($data) || empty($data)) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'EMPTY_DATA',
                errorMessage:    'CoinEx returned success with empty data',
            );
        }

        $orderId       = isset($data['order_id']) ? (string) $data['order_id'] : null;
        $status        = $this->mapStatus((string) ($data['status'] ?? ''));
        $filledAmount  = (string) ($data['filled_amount'] ?? '0');
        $avgPrice      = $this->resolveAvgPrice($data);
        $exchangeFee   = $this->resolveFee($data, $avgPrice);

        return new ExchangeOrderResult(
            exchangeOrderId: $orderId,
            status:          $status,
            filledAmount:    $filledAmount,
            avgPrice:        $avgPrice,
            exchangeFee:     $exchangeFee,
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
     * Normalize exchange fee to quote currency (USDT) regardless of which fee
     * field CoinEx populated for this side.
     */
    private function resolveFee(array $data, string $avgPrice): string
    {
        $quoteFee = (string) ($data['quote_fee'] ?? '0');
        if (bccomp($quoteFee, '0', 8) > 0) {
            return $quoteFee;
        }
        $baseFee = (string) ($data['base_fee'] ?? '0');
        if (bccomp($baseFee, '0', 8) > 0 && bccomp($avgPrice, '0', 8) > 0) {
            return bcmul($baseFee, $avgPrice, 8);
        }
        return '0';
    }

    private function quoteOf(string $market): string
    {
        // Conventional CoinEx pair: BASE+QUOTE concatenated. Bot trades against USDT.
        return str_ends_with($market, 'USDT') ? 'USDT' : substr($market, -3);
    }
}
