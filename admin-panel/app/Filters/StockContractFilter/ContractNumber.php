<?php

namespace App\Filters\StockContractFilter;

use App\Filters\FilterContract;

class ContractNumber implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value)) {
            $this->query->where('contract_number', 'LIKE', '%' . $value . '%');
        }
    }
}
