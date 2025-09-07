<?php

namespace App\Filters\TicketFilters;

use App\Filters\FilterContract;

class Message implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value) && $value !== '') {
            // Search within related replies' message content
            $this->query->whereHas('replies', function ($q) use ($value) {
                $q->where('message', 'LIKE', '%' . $value . '%');
            });
        }
    }
}
