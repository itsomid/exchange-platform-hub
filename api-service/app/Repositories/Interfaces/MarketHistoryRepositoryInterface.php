<?php

namespace App\Repositories\Interfaces;

use App\Models\MarketHistory;
use Carbon\Carbon;

interface MarketHistoryRepositoryInterface
{
    public function getByMarketIdWithDate(int $marketId, Carbon $date): ?MarketHistory;

    public function getByMarketIdWithDateTime(int $marketId, Carbon $dateTime): ?MarketHistory;

    /**
     * Get 7-day downsampled close price series keyed by uppercase base currency symbol.
     *
     * @param  list<string>  $symbols  Optional list of base currency symbols to fetch; empty fetches active markets.
     * @return array<string, list<float>>
     */
    public function getWeeklySparklines(array $symbols = [], int $maxPoints = 168): array;
}
