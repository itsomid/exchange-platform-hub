<?php

namespace App\Services\Exchanges\Asset\Coinex\Authentication;

class CoinexAuthentication
{
    /**
     * CoinEx v2: method + request_path[+?query] + body(optional) + timestamp
     *
     * @see https://docs.coinex.com/api/v2/authorization
     * @see https://github.com/coinexcom/coinex_api_demo/blob/feat-api-v2/python/api.py
     */
    public static function getSigned(MethodEnum $method, string $requestPath, int|string $timestamp, string $body = ''): string
    {
        $preparedStr = $method->value . $requestPath . $body . $timestamp;

        return strtolower(hash_hmac('sha256', $preparedStr, config('exchanges.coinex.secret_key')));
    }

    /**
     * Build a stable query string (same encoding Guzzle/CoinEx demos expect).
     */
    public static function buildQueryString(array $params): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $filtered[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        return http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
    }
}
