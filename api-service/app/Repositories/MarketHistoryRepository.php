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
            ->first();
    }
}
