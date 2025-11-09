<?php

namespace App\Filters\StockContractFilter;

use App\Filters\FilterContract;

class StockType implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value)) {
            $this->query->whereHas('stock', function ($q) use ($value) {
                $q->where('type', $value);
            });
        }
    }
}