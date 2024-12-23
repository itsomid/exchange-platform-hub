<?php

namespace App\Services\Exchanges\MarketHistory;

class MarketHistoryFactory
{
    public static function make(string $exchange): MarketHistoryFetcher
    {
        return match ($exchange) {
            'coinex' => new CoinExMarketHistoryFetcher(),
            // Add other exchanges like 'kucoin', 'binance', etc.
            default => throw new \InvalidArgumentException("Exchange [{$exchange}] is not supported."),
        };
    }
}
