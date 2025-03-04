<?php

namespace App\Repositories;

use App\Enums\LockedBalanceTypeEnum;
use App\Models\LockedBalanceDetail;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;

class LockedBalanceRepository implements LockedBalanceRepositoryInterface
{
    public function createLockedBalance(array $data): LockedBalanceDetail
    {
        return LockedBalanceDetail::query()
            ->create($data);
    }

    public function deleteWithdrawalLockedBalance(int $withdrawalId): void
    {
        LockedBalanceDetail::query()
            ->where('type', LockedBalanceTypeEnum::WITHDRAWAL)
            ->where('withdrawal_id', $withdrawalId)
            ->delete();
    }
}
