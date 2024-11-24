<?php

namespace App\Filters\UserFilter;

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
            $this->query->where('username', $value)->orWhere('email', 'LIKE', '%'.$value.'%')->orWhere('last_name', 'LIKE', '%'.$value.'%');
        }

    }
}
