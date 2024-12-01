<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function getCurrencyWithChains(string $symbol): Currency
    {
        return Currency::query()
            ->with('chains')
            ->where('symbol', $symbol)
            ->first();
    }
}
