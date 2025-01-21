<?php

namespace App\Filters\OTCOrderFilter;

use App\Filters\FilterContract;
use Illuminate\Database\Eloquent\Builder;

class CreatedAt implements FilterContract
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function handle($value): void
    {
        if (! is_null($value) && preg_match('/^\d{4}-\d{2}-\d{2}( .+)?, ?\d{4}-\d{2}-\d{2}( .+)?$/', $value)) {
            [$start, $end] = explode(',', $value);
            $this->query->whereBetween('created_at', [$start, $end]);
        }
    }
}
