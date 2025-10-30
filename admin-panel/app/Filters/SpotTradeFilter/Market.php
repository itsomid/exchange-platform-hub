<?php

namespace App\Filters\SpotTradeFilter;

use App\Filters\FilterContract;

class Market implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value)) {
            $this->query->where('market_id', $value);
        }
    }
}