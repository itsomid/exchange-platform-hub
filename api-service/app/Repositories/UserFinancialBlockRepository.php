<?php

namespace App\Repositories;

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
}
