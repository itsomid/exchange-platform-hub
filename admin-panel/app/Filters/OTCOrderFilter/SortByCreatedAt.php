<?php

namespace App\Filters\OTCOrderFilter;

use App\Filters\FilterContract;

class SortByCreatedAt implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        $value == 'desc'
            ? $this->query->latest()
            : $this->query->oldest();
    }
}
