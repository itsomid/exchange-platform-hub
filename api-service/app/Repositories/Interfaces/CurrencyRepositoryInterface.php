<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepositoryInterface
{
    public function getCurrencyWithChains(string $symbol);

    public function getAllCurrencyWithChains(): Collection;
}
