<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Services\Exchanges\Asset\Coinex\Authentication\CoinexAuthentication;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CoinexRequest
{
    public static function send(MethodEnum $method, string $path, array $data = []): \Illuminate\Http\Client\Response
    {
//        dd(CoinexAuthentication::getSigned($method, $path, $timestamp = time()));
        return Http::baseUrl(config('exchanges.coinex.base_url_v2'))
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof ConnectionException;
            })
            ->withHeaders([
                'X-COINEX-KEY' => config('exchanges.coinex.access_id'),
                'X-COINEX-SIGN' => CoinexAuthentication::getSigned($method, $path, $timestamp = round(microtime(true) * 1000), $data),
                'X-COINEX-TIMESTAMP' => $timestamp
            ])
        ->{$method->value}($path, $data);
    }
}
