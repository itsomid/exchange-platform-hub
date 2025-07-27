<?php

namespace App\Filters\TransactionFilter;

use App\Filters\FilterContract;

class StockContractId implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where('stock_contract_id', $value);
        }
    }
}
