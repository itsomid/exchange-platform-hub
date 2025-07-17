<?php

namespace App\Repositories\Interfaces;

use App\Models\Exchange;

interface ExchangeRepositoryInterface
{
    public function getActiveExchange(): Exchange;
    
    public function getExchangeBySlug(string $slug): ?Exchange;
}
