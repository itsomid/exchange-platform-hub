<?php

namespace App\Filters\WithdrawalFilter;

use App\Filters\FilterContract;

class SortByTotalFee implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value == 'desc') {
            $this->query->orderBy('total_fee', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('total_fee', $value);
        }
    }
}
