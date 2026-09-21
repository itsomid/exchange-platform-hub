<?php

namespace App\Services\Exchanges\Asset\Binance;

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
     * Send a signed request to Binance API.
     *
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
}
