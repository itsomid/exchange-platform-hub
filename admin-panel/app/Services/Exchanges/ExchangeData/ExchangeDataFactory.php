<?php

namespace App\Services\Exchanges\ExchangeData;

class ExchangeDataFactory
{
    public static function make(string $exchange): ExchangeDataInterface
    {
        return match ($exchange) {
            'binance' => new BinanceExchangeData(),
            default => throw new \InvalidArgumentException("Exchange [{$exchange}] is not supported."),
        };
    }
}
