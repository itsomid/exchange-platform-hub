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
            return Market::query()->whereIsActive(true)->with('currency')->get();
        });
    }

    public function getMarketById(int $marketId): ?Market
    {
        return Market::query()
            ->find($marketId);
    }
    public function getMarketBySymbol(string $baseCurrency,string $quoteCurrency): ?Market
    {
        return Market::query()
            ->whereBaseCurrency($baseCurrency)
            ->whereQuoteCurrency($quoteCurrency)
            ->first();
    }

    public function getAll(): Collection
    {
        return Cache::remember(__CLASS__.'getAll', 60, function () {
            return Market::query()->get();
        });
    }

    public function getActiveMarket() :Collection
    {
        return Cache::remember(__CLASS__.'getActiveMarket', 60, function () {
            return Market::query()->whereIsActive(true)->get();
        });
    }
}
