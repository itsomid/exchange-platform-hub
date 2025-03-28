<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface SpotTradeRepositoryInterface
{
    public function getLatest(int $marketId): Collection;
}
