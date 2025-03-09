<?php

namespace App\Filters\ReferralCodeFilters;

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
        if ($value) {
            $this->query->withSum('transactions', 'amount')
                       ->orderBy('transactions_sum_amount', $value);
        }
    }
}
