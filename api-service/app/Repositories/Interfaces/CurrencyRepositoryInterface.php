<?php

namespace App\Repositories\Interfaces;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepositoryInterface
{
    public function getOne(string $symbol): Currency;

    public function getCurrencyWithChains(string $symbol);

    public function getAllCurrencyWithChains(): Collection;
}
