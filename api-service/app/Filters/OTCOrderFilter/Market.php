<?php

namespace App\Filters\OTCOrderFilter;

use App\Filters\FilterContract;
use Illuminate\Database\Eloquent\Builder;

class Market implements FilterContract
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function handle($value): void
    {
        if (is_numeric($value)) {
            $this->query->where('market_id', $value);
        }
    }
}
