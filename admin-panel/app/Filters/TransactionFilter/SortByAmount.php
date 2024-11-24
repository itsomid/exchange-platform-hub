<?php

namespace App\Filters\TransactionFilter;

use App\Filters\FilterContract;

class SortByAmount implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value == 'desc') {
            $this->query->orderBy('amount', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('amount', $value);
        }
    }
}
