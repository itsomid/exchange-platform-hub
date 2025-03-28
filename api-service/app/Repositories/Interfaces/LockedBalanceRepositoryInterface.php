<?php

namespace App\Repositories\Interfaces;

use App\Enums\LockedBalanceTypeEnum;
use App\Models\LockedBalanceDetail;

interface LockedBalanceRepositoryInterface
{
    public function createLockedBalance(array $data): LockedBalanceDetail;

    public function deleteWithdrawalLockedBalance(int $withdrawalId): void;

    public function deleteSpotOrderLockedBalance(int $spotOrderId): void;

    public function getOne(int $typeId, LockedBalanceTypeEnum $type): ?LockedBalanceDetail;
}
