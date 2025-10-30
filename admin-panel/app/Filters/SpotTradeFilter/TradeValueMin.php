<?php

namespace App\Filters\SpotTradeFilter;

use App\Filters\FilterContract;

class TradeValueMin implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value) && is_numeric($value)) {
            $this->query->whereRaw('(price * quantity) >= ?', [$value]);
        }
    }
}
