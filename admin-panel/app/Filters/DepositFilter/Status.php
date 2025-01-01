<?php

namespace App\Filters\DepositFilter;

use App\Filters\FilterContract;

class Status implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {

        if (! is_null($value)) {
            $this->query->where('status', $value);
        }
    }
}
