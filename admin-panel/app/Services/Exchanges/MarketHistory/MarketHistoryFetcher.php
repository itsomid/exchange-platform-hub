<?php

namespace App\Services\Exchanges\MarketHistory;

interface MarketHistoryFetcher
{
    public function fetchHistory(string $market, string $period, int $limit): array;
}
