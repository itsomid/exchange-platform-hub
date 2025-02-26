<?php

namespace App\Services\User;

use App\Enums\UserFinancialBlockAction;
use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;
use App\Repositories\UserFinancialBlockRepository;
use App\Services\User\DTO\FinancialBlock\GetUserBlockedStateResponseDTO;
use App\Services\User\DTO\FinancialBlock\SaveFinancialBlockRequestDTO;

class FinancialBlockService
{
    public function __construct(private readonly UserFinancialBlockRepository $repository) {}

    public function saveOrUpdateState(SaveFinancialBlockRequestDTO $requestDTO): void
    {
        $this->repository->saveNewState(
            resolve(SaveOrUpdateBlockStateRequestDTO::class)
                ->setUserId($requestDTO->getUserId())
                ->setReason($requestDTO->getReason())
                ->setRestrictedUntil($requestDTO->getRestrictedUntil())
                ->setAction($requestDTO->getAction())
        );
    }

    public function getUserBlockedState(int $userId, UserFinancialBlockAction $action): GetUserBlockedStateResponseDTO
    {
        $userBlockModel = $this->repository->getLatestUserBlock($userId, $action);

        if (is_null($userBlockModel)) {
            return resolve(GetUserBlockedStateResponseDTO::class)
                ->setIsBlock(false);
        }

        return resolve(GetUserBlockedStateResponseDTO::class)
            ->setIsBlock($userBlockModel->restricted_until->isFuture())
            ->setRestrictUntil($userBlockModel->restricted_until);
    }
}
