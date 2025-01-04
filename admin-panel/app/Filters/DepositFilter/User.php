<?php

namespace App\Filters\DepositFilter;

use App\Filters\FilterContract;

class User implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {

        if (! is_null($value)) {
            $this->query->where('user_id', $value);
        }
    }
}
