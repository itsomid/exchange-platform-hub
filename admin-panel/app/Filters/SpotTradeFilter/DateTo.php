<?php

namespace App\Filters\SpotTradeFilter;

use App\Filters\FilterContract;
use App\Helpers\DateFormatter;

class DateTo implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (!is_null($value)) {
            // Convert Persian date to Carbon date
            $carbonDate = DateFormatter::convertPersianToCarbonDate($value);
            $this->query->whereDate('created_at', '<=', $carbonDate);
        }
    }
}