<?php

namespace App\Repositories;

use App\Models\MarketHistory;
use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use Carbon\Carbon;

class MarketHistoryRepository implements MarketHistoryRepositoryInterface
{
    public function getByMarketIdWithDate(int $marketId, Carbon $date): ?MarketHistory
    {
        return MarketHistory::query()->where('market_id', $marketId)
            ->whereDate('timestamp', $date)
            ->latest()
            ->first();
    }

    public function getByMarketIdWithDateTime(int $marketId, Carbon $dateTime): ?MarketHistory
    {
        $startOfHour = $dateTime->copy()->startOfHour();
        $endOfHour = $dateTime->copy()->endOfHour();

        return MarketHistory::query()->where('market_id', $marketId)
            ->whereBetween('timestamp', [$startOfHour, $endOfHour])
            ->latest('timestamp')
            ->first();
    }
}
