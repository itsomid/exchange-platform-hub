<?php

namespace App\Filters\TransactionFilter;

use App\Filters\FilterContract;

class FromId implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value) && is_numeric($value)) {
            $this->query->where('id', '>=', $value);
        }
    }
}
