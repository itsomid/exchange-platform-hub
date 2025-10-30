<?php

namespace App\Filters\SpotTradeFilter;

use App\Filters\FilterContract;

class QuantityMin implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value) && is_numeric($value)) {
            $this->query->where('quantity', '>=', $value);
        }
    }
}
