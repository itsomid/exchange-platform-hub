<?php

namespace App\Filters\TicketFilters;

use App\Filters\FilterContract;

class Subject implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value) && $value !== '') {
            $this->query->where('subject', 'LIKE', '%' . $value . '%');
        }
    }
}
