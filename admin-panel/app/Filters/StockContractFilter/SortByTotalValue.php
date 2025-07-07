<?php

namespace App\Filters\StockContractFilter;

use App\Filters\FilterContract;

class SortByTotalValue implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value == 'desc') {
            $this->query->orderBy('total_value', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('total_value', $value);
        }
    }
}
