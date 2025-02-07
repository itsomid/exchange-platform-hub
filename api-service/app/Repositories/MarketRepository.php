<?php

namespace App\Repositories;

use App\Models\Market;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MarketRepository implements MarketRepositoryInterface
{
    public function getOTCMarkets(): Collection
    {
        return Cache::remember(__CLASS__.'getOTCMarkets', 60, function () {
            return Market::query()->with('currency')->get();
        });
    }

    public function getMarketById(int $marketId): Market
    {
        return Market::query()
            ->find($marketId);
    }

    public function getAll(): Collection
    {
        return Cache::remember(__CLASS__.'getAll', 60, function () {
            return Market::query()->get();
        });
    }
}
