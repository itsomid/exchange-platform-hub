<?php

namespace App\Filters\SpotTradeFilter;

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
            // Filter by either maker or taker user
            $this->query->where(function ($query) use ($value) {
                $query->whereHas('makerOrder', function ($q) use ($value) {
                    $q->where('user_id', $value);
                })->orWhereHas('takerOrder', function ($q) use ($value) {
                    $q->where('user_id', $value);
                });
            });
        }
    }
}