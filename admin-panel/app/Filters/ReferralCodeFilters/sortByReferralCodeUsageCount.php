<?php

namespace App\Filters\ReferralCodeFilters;

use App\Filters\FilterContract;

class SortByReferralCodeUsageCount implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if ($value) {
            $this->query->withCount('referralCodeUsage')
                       ->orderBy('referral_code_usage_count', $value);
        }
    }
}
