<?php

namespace App\Filters\CurrencyFilter;

use App\Filters\FilterContract;

class Chain implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->whereHas('chains', function ($q) use ($value) {
                $q->where('chain', $value);
            });
        }
    }
}
