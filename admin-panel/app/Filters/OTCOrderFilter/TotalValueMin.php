<?php

namespace App\Filters\OTCOrderFilter;

use App\Filters\FilterContract;

class TotalValueMin implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value) && $value !== '') {
            $this->query->havingRaw('(price * quantity) >= ?', [$value]);
        }
    }
}
