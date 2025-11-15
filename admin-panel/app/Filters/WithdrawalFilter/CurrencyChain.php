<?php

namespace App\Filters\WithdrawalFilter;

use App\Filters\FilterContract;

class CurrencyChain implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->whereHas('currencyChain', function ($query) use ($value) {
                $query->where('chain', $value);
            });
        }
    }
}
