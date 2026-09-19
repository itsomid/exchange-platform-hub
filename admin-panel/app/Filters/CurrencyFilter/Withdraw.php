<?php

namespace App\Filters\CurrencyFilter;

use App\Filters\FilterContract;

class Withdraw implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value === 'enabled') {
            $this->query->whereHas('chains', fn ($q) => $q->where('withdraw_enabled', true));
        } elseif ($value === 'disabled') {
            $this->query->whereDoesntHave('chains', fn ($q) => $q->where('withdraw_enabled', true));
        }
    }
}
