<?php

namespace App\Repositories;


use App\Models\Exchange;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;

class ExchangeRepository implements ExchangeRepositoryInterface
{
    public function getActiveExchange(): Exchange
    {
        return Exchange::query()
            ->where('is_active', true)
            ->first();
    }

}
