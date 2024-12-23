<?php

namespace App\Repositories\Interfaces;

use App\Models\MarketHistory;
use Carbon\Carbon;

interface MarketHistoryRepositoryInterface
{
    public function getByMarketIdWithDate(int $marketId, Carbon $date): ?MarketHistory;
}
