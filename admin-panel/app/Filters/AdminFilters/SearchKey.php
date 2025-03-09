<?php

namespace App\Filters\AdminFilters;

class SearchKey implements \App\Filters\FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where('id', $value)->orWhere('mobile', 'LIKE', '%'.$value.'%')
            ->orWhere('first_name', 'LIKE', '%'.$value.'%')
            ->orWhere('last_name', 'LIKE', '%'.$value.'%')
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$value.'%']);
        }
    }
}
