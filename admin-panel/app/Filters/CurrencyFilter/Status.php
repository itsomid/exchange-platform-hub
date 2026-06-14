<?php

namespace App\Filters\CurrencyFilter;

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
        if ($value === 'active') {
            $this->query->whereHas('chains');
        } elseif ($value === 'inactive') {
            $this->query->whereDoesntHave('chains');
        }
    }
}
