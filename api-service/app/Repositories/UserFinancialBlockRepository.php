<?php

namespace App\Repositories;

use App\Enums\FinancialBlockActionEnum;
use App\Models\UserFinancialBlock;
use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;
use App\Repositories\Interfaces\UserFinancialBlockRepositoryInterface;

class UserFinancialBlockRepository implements UserFinancialBlockRepositoryInterface
{
    public function saveNewState(SaveOrUpdateBlockStateRequestDTO $requestDTO): void
    {
        UserFinancialBlock::query()
            ->create([
                'user_id' => $requestDTO->getUserId(),
                'action' => $requestDTO->getAction(),
                'restricted_until' => $requestDTO->getRestrictedUntil(),
                'reason' => $requestDTO->getReason(),
            ]);
    }

    public function getLatestUserBlock(int $userId, FinancialBlockActionEnum $action): ?UserFinancialBlock
    {
        return UserFinancialBlock::query()
            ->where('user_id', $userId)
            ->where('action', $action)
            ->activeRestriction()
            ->latest()
            ->first();
    }
}
