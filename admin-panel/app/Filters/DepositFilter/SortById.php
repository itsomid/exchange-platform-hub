<?php

namespace App\Filters\DepositFilter;

use App\Filters\FilterContract;

class SortById implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value == 'desc') {
            $this->query->orderBy('id', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('id', $value);
        }
    }
}
