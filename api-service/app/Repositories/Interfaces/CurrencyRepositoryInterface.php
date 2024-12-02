<?php

namespace App\Repositories\Interfaces;

interface CurrencyRepositoryInterface
{
    public function getCurrencyWithChains(string $symbol);
}
