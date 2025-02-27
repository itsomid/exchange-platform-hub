<?php

namespace App\Filters\TicketFilters;

use App\Filters\FilterContract;

class SearchKey implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where('id', $value)->orWhere('mobile', 'LIKE', '%'.$value.'%')->orWhere('first_name', 'LIKE', '%'.$value.'%')->orWhere('last_name', 'LIKE', '%'.$value.'%');
        }
    }
}
