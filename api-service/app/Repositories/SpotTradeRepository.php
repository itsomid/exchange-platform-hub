<?php

namespace App\Repositories;

use App\Models\SpotTrade;
use App\Repositories\Interfaces\SpotTradeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SpotTradeRepository implements SpotTradeRepositoryInterface
{
    public function getLatest(int $marketId): Collection
    {
        return SpotTrade::query()
            ->where('market_id', $marketId)
            ->latest()
            ->limit(20)
            ->get();
    }
}
