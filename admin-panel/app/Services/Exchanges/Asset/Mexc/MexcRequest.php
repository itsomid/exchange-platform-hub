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
     * Get coin configuration from MEXC API
     *
     * @return array|null
     */
    public static function getCoinConfig(): ?array
    {
        try {
            $response = self::sendRequest('GET', '/api/v3/capital/config/getall');

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::channel('ref-exchange')->error('Failed to fetch MEXC coin config', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('ref-exchange')->error('Exception while fetching MEXC coin config', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Send a withdrawal request to MEXC API
     *
     * @param string $method HTTP method (POST)
     * @param string $path API endpoint path ('/api/v3/capital/withdraw')
     * @param array $params Request parameters (coin, address, amount, network, memo)
     * @return \Illuminate\Http\Client\Response
     */
    public static function sendWithdrawal(string $method, string $path, array $params = [])
    {
        $apiKey = config('exchanges.mexc.api_key');
        $secretKey = config('exchanges.mexc.secret_key');
        $baseUrl = config('exchanges.mexc.base_url');
    
        $params['timestamp'] = self::getServerTime() ?? (int) round(microtime(true) * 1000);
        
        // Build ordered parameters for withdrawal API
        $ordered = [
            'coin' => $params['coin'],
            'address' => $params['address'],
            'amount' => $params['amount'],
        ];
        // Add network if provided
        if (isset($params['network'])) {
            $ordered['netWork'] = $params['network'];
        }
        
        // Add memo if provided
        if (isset($params['memo'])) {
            $ordered['memo'] = $params['memo'];
        }
        
        // Add timestamp
        $ordered['timestamp'] = $params['timestamp'];
  
        $queryString = http_build_query($ordered, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey);
        $ordered['signature'] = $signature;
      
        $url = $baseUrl . $path . '?' . http_build_query($ordered, '', '&', PHP_QUERY_RFC3986);
        // dd($url);
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
        
        // Only add recvWindow if it's explicitly provided or needed
        // Don't add default recvWindow for withdrawal endpoints
        if (!isset($params['recvWindow']) && !str_contains($path, 'withdraw')) {
            $params['recvWindow'] = '60000';
        }

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
