<?php

namespace App\Repositories;

use App\Models\UserFinancialBlock;
use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;
use App\Repositories\Interfaces\UserFinancialBlockRepositoryInterface;

class UserFinancialBlockRepository implements UserFinancialBlockRepositoryInterface
{
    public function saveOrUpdateState(SaveOrUpdateBlockStateRequestDTO $requestDTO): void
    {
        $userBlock = UserFinancialBlock::query()
            ->where('user_id', $requestDTO->getUserId())
            ->where('action', $requestDTO->getAction())
            ->activeRestriction()
            ->first();

        if (! $userBlock) {
            UserFinancialBlock::query()
                ->create([
                    'user_id' => $requestDTO->getUserId(),
                    'action' => $requestDTO->getAction(),
                    'restricted_until' => $requestDTO->getRestrictedUntil(),
                    'reason' => $requestDTO->getReason(),
                ]);
        } else {
            $userBlock->update(['restricted_until' => $requestDTO->getRestrictedUntil()]);
        }
    }
}
