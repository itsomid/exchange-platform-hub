<?php

namespace App\Filters\TransactionFilter;

use App\Filters\FilterContract;
use App\Helpers\DateFormatter;

class ToDate implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $carbonDate = DateFormatter::convertPersianToCarbonDate($value);
            $this->query->whereDate('created_at', '<=', $carbonDate);
        }
    }
}
