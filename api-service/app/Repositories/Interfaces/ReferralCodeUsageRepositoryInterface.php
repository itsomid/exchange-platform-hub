<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface ReferralCodeUsageRepositoryInterface
{
    public function getReceivedProfits(int $userId): Collection;
}
