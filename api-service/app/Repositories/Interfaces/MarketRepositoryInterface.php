<?php

namespace App\Repositories\Interfaces;

use App\Models\Market;
use Illuminate\Support\Collection;

interface MarketRepositoryInterface
{
    public function getOTCMarkets(): Collection;

    public function getAll(): Collection;

    public function getMarketById(int $marketId): ?Market;
}
