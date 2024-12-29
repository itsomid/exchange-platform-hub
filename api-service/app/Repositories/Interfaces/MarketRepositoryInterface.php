<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface MarketRepositoryInterface
{
    public function getOTCMarkets(): Collection;
}
