<?php

namespace App\Repositories\Interfaces;

use App\Models\LockedBalanceDetail;

interface LockedBalanceRepositoryInterface
{
    public function createLockedBalance(array $data): LockedBalanceDetail;

    public function deleteWithdrawalLockedBalance(int $withdrawalId): void;
}
