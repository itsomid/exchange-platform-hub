<?php

namespace App\Repositories;

use App\Models\ReferralCodeUsage;
use App\Repositories\Interfaces\ReferralCodeUsageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReferralCodeUsageRepository implements ReferralCodeUsageRepositoryInterface
{
    public function getReceivedProfits(int $userId): Collection
    {
        return ReferralCodeUsage::query()
            ->where('used_by', $userId)
            ->with('transaction')
            ->get();
    }
}
