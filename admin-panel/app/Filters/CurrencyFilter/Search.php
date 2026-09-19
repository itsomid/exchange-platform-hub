<?php

namespace App\Filters\CurrencyFilter;

use App\Filters\FilterContract;

class Search implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where(function ($q) use ($value) {
                $q->where('name', 'LIKE', "%{$value}%")
                    ->orWhere('symbol', 'LIKE', "%{$value}%");
            });
        }
    }
}
