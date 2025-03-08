<?php

namespace App\Filters\ReferralCodeFilters;

use App\Filters\FilterContract;

class SortByRegisteredUserCount implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value) {
            $this->query->withCount('registeredUsers')
                       ->orderBy('registered_users_count', $value);
        }
    }
}
