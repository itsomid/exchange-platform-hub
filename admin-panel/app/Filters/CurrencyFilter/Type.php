<?php

namespace App\Filters\UserFilter;

use App\Filters\FilterContract;

class Type implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {

        if (! is_null($value)) {
            $this->query->where('type', $value);
        }
    }
}
