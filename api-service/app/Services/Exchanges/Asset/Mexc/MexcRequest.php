<?php

namespace App\Services\Exchanges\Asset\Mexc;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MexcRequest
{
    /**
     * Get the current server time from MEXC.
     *
     * @return int|null
     */
    public static function getServerTime(): ?int
    {
        try {
            $baseUrl = config('exchanges.mexc.base_url');
            $response = Http::get($baseUrl . '/api/v3/time');
            if ($response->successful()) {
                return $response->json('serverTime');
            }
        } catch (\Throwable $e) {
            Log::channel('ref-exchange')->error('Failed to fetch MEXC server time', ['error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Send a signed request to MEXC API (for POST/GET/DELETE with signature)
     *
     * @param string $method HTTP method (GET, POST, etc)
     * @param string $path API endpoint path (e.g. '/api/v3/order')
     * @param array $params Request parameters (query/body)
     * @param bool $isJsonBody Whether to send as JSON body (default: false, for MEXC use form params)Timestamp for this request is outside of the recvWindow
     * @return \Illuminate\Http\Client\Response
     */
    public static function send(string $method, string $path, array $params = [])
    {
        $apiKey = config('exchanges.mexc.api_key');
        $secretKey = config('exchanges.mexc.secret_key');
        $baseUrl = config('exchanges.mexc.base_url');
    
        $params['timestamp'] = self::getServerTime() ?? (int) round(microtime(true) * 1000);
        
        $params['recvWindow'] = $params['recvWindow'] ?? '60000';

        // Build ordered parameters for MEXC API
        $ordered = [
            'symbol' => $params['symbol'],
            'side' => strtoupper($params['side']),
            'type' => strtoupper($params['type']),
            'quantity' => formatNumberTrimZeros($params['quantity'],$params['amount_precision'] ?? null),
        ];
        
        // Add price parameter for LIMIT orders
        if (isset($params['price']) && strtoupper($params['type']) === 'LIMIT') {
            $ordered['price'] = $params['price'];
        }
        
        // Add timestamp and recvWindow
        $ordered['timestamp'] = $params['timestamp'];
        $ordered['recvWindow'] = $params['recvWindow'];
  
        $queryString = http_build_query($ordered, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey);
        $ordered['signature'] = $signature;
      
        $url = $baseUrl . $path . '?' . http_build_query($ordered, '', '&', PHP_QUERY_RFC3986);
        
        return Http::withHeaders([
            'X-MEXC-APIKEY' => $apiKey,
        ])->send(strtoupper($method), $url);
    }

        /**
     * Send a generic signed request to MEXC API
     *
     * @param string $method HTTP method (GET, POST, etc)
     * @param string $path API endpoint path (e.g. '/api/v3/mxDeduct/enable')
     * @param array $params Request parameters
     * @return \Illuminate\Http\Client\Response
     */
    public static function sendRequest(string $method, string $path, array $params = [])
    {
        $apiKey = config('exchanges.mexc.api_key');
        $secretKey = config('exchanges.mexc.secret_key');
        $baseUrl = config('exchanges.mexc.base_url');

        $params['timestamp'] = self::getServerTime() ?? (int) round(microtime(true) * 1000);
        $params['recvWindow'] = $params['recvWindow'] ?? '60000';

        // Sort parameters for consistent signature
        ksort($params);

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey);
        $params['signature'] = $signature;

        $url = $baseUrl . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        return Http::withHeaders([
            'X-MEXC-APIKEY' => $apiKey,
        ])->send(strtoupper($method), $url);
    }

}
