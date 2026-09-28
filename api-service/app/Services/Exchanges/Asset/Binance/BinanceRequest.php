<?php

namespace App\Services\Exchanges\Asset\Binance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceRequest
{
    public static function getServerTime(): ?int
    {
        try {
            $baseUrl = config('exchanges.binance.base_url');
            $response = Http::get($baseUrl . '/api/v3/time');
            if ($response->successful()) {
                return $response->json('serverTime');
            }
        } catch (\Throwable $e) {
            Log::channel('ref-exchange')->error('Failed to fetch Binance server time', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @return \Illuminate\Http\Client\Response
     */
    public static function sendRequest(string $method, string $path, array $params = [])
    {
        $apiKey = config('exchanges.binance.api_key');
        $secretKey = config('exchanges.binance.secret_key');
        $baseUrl = config('exchanges.binance.base_url');

        $params['timestamp'] = self::getServerTime() ?? (int) round(microtime(true) * 1000);

        if (!isset($params['recvWindow'])) {
            $params['recvWindow'] = '60000';
        }

        ksort($params);

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey);
        $params['signature'] = $signature;

        $url = $baseUrl . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $apiKey,
        ])->send(strtoupper($method), $url);
    }

    /**
     * @return array<string, string> LOT_SIZE / MARKET_LOT_SIZE / PRICE_FILTER / NOTIONAL values for the symbol
     */
    public static function symbolFilters(string $symbol): array
    {
        return Cache::remember('binance:symbol-filters:' . $symbol, now()->addHour(), function () use ($symbol) {
            $response = Http::timeout(10)->get(config('exchanges.binance.base_url') . '/api/v3/exchangeInfo', [
                'symbol' => $symbol,
            ]);

            if (! $response->ok()) {
                throw new \RuntimeException("Binance exchangeInfo failed for {$symbol}: " . $response->body());
            }

            $filters = [];
            foreach ($response->json('symbols.0.filters') ?? [] as $filter) {
                switch ($filter['filterType'] ?? null) {
                    case 'LOT_SIZE':
                        $filters['stepSize'] = (string) $filter['stepSize'];
                        $filters['minQty'] = (string) $filter['minQty'];
                        break;
                    case 'MARKET_LOT_SIZE':
                        $filters['marketStepSize'] = (string) $filter['stepSize'];
                        $filters['marketMinQty'] = (string) $filter['minQty'];
                        break;
                    case 'PRICE_FILTER':
                        $filters['tickSize'] = (string) $filter['tickSize'];
                        break;
                    case 'NOTIONAL':
                    case 'MIN_NOTIONAL':
                        $filters['minNotional'] = (string) $filter['minNotional'];
                        $filters['minNotionalAppliesToMarket'] = ($filter['applyMinToMarket'] ?? $filter['applyToMarket'] ?? true) ? '1' : '0';
                        break;
                }
            }

            return $filters;
        });
    }

    /**
     * Binance checks the NOTIONAL filter of MARKET orders against this price, not the last trade.
     */
    public static function averagePrice(string $symbol): string
    {
        $response = Http::timeout(10)->get(config('exchanges.binance.base_url') . '/api/v3/avgPrice', [
            'symbol' => $symbol,
        ]);

        if (! $response->ok() || ! is_numeric($response->json('price'))) {
            throw new \RuntimeException("Binance avgPrice failed for {$symbol}: " . $response->body());
        }

        return (string) $response->json('price');
    }
}
