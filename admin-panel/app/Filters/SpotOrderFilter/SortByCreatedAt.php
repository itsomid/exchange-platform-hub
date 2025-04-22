<?php

namespace App\Filters\SpotOrderFilter;

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
        if ($value == 'desc') {
            $this->query->orderBy('created_at', $value);
        }

        if ($value == 'asc') {
            $this->query->orderBy('created_at', $value);
        }
    }
}
