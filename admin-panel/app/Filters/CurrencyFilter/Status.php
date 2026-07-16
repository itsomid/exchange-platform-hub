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
            $this->query->where('is_active', true);
        } elseif ($value === 'inactive') {
            $this->query->where('is_active', false);
        } elseif ($value === 'no_chains') {
            $this->query->whereDoesntHave('chains');
        }
    }
}
