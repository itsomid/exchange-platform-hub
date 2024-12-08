<?php

namespace App\Repositories;

use App\Models\ReferralCodeUsage;
use App\Repositories\Interfaces\ReferralCodeUsageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ReferralCodeUsageRepository implements ReferralCodeUsageRepositoryInterface
{
    public function getReceivedProfits(int $userId): Collection
    {
        return Cache::remember(__CLASS__.'.getReceivedProfits.'.$userId, now()->addHours(1), fn () => ReferralCodeUsage::query()
            ->where('used_by', $userId)
            ->with('transaction')
            ->get());
    }
}
