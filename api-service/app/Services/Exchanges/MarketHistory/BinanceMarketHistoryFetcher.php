<?php

namespace App\Services\Exchanges\MarketHistory;

class BinanceMarketHistoryFetcher implements MarketHistoryFetcher
{
    public function fetchHistory(string $market, string $period, int $limit): array
    {
        return [];
    }
}
