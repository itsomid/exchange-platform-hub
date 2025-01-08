<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function getOne(string $symbol): Currency
    {
        return Currency::query()
            ->where('symbol', $symbol)
            ->first();
    }

    public function getCurrencyWithChains(string $symbol): Currency
    {
        return Currency::query()
            ->with('chains')
            ->where('symbol', $symbol)
            ->first();
    }

    public function getAllCurrencyWithChains(): Collection
    {
        return Currency::query()
            ->with('chains')
            ->get();
    }
}
