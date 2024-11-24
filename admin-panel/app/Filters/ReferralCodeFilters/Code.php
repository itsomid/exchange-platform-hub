<?php

namespace App\Filters\ReferralCodeFilters;

class Code implements \App\Filters\FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where('code', $value);
        }
    }
}
