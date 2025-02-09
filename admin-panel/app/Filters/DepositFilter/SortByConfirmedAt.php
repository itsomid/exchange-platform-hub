<?php

namespace App\Filters\DepositFilter;

use App\Filters\FilterContract;

class SortByConfirmedAt implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value == 'desc') {
            $this->query->orderBy('confirmed_at', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('confirmed_at', $value);
        }
    }
}
