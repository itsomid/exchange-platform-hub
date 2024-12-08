<?php

namespace App\Services\Exchanges\WithdrawalFee;

class ExchangeFactory
{
    public static function make(string $exchange): ExchangeInterface
    {
        return match ($exchange) {
            'coinex' => new CoinexService,
            // Add other exchanges like 'kucoin', 'binance', etc.
            default => throw new \InvalidArgumentException("Exchange [{$exchange}] is not supported."),
        };
    }
}
