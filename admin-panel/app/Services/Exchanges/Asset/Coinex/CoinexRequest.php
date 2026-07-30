<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Services\Exchanges\Asset\Coinex\Authentication\CoinexAuthentication;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CoinexRequest
{
    public static function send(MethodEnum $method, string $path, array $data = []): \Illuminate\Http\Client\Response
    {
        $timestamp = (string) (int) round(microtime(true) * 1000);
        $isGetLike = in_array($method, [MethodEnum::GET, MethodEnum::DELETE], true);

        if ($isGetLike) {
            $queryString = CoinexAuthentication::buildQueryString($data);
            // Official CoinEx v2 demo: query string is part of the signed request_path.
            $requestPathForSign = $queryString !== '' ? $path . '?' . $queryString : $path;
            $bodyForSign = '';
            $httpPath = $requestPathForSign;
        } else {
            $requestPathForSign = $path;
            // Keep default json_encode flags — must match what Laravel/Guzzle sends on POST.
            $bodyForSign = count($data) ? json_encode($data) : '';
            $httpPath = $path;
        }

        $pending = self::client()->withHeaders([
            // Required by CoinEx even for GET (official api demo).
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'X-COINEX-KEY' => config('exchanges.coinex.access_id'),
            'X-COINEX-SIGN' => CoinexAuthentication::getSigned($method, $requestPathForSign, $timestamp, $bodyForSign),
            'X-COINEX-TIMESTAMP' => $timestamp,
        ]);

        // IMPORTANT: do not call get($url, []) — the empty query option clears a baked-in ?query.
        if ($isGetLike) {
            return $method === MethodEnum::DELETE
                ? $pending->delete($httpPath)
                : $pending->get($httpPath);
        }

        return $pending->{$method->value}($httpPath, $data);
    }

    private static function client(): PendingRequest
    {
        return Http::baseUrl(config('exchanges.coinex.base_url_v2'))
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof ConnectionException;
            });
    }
}
