<?php

namespace App\Filters\OTCOrderFilter;

use App\Filters\FilterContract;

class DateFrom implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value) && $value !== '') {
            $this->query->whereDate('created_at', '>=', $value);
        }
    }
}
