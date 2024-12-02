<?php

namespace App\Services\User;

use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;
use App\Repositories\UserFinancialBlockRepository;
use App\Services\User\DTO\FinancialBlock\SaveFinancialBlockRequestDTO;

class FinancialBlockService
{
    public function __construct(private readonly UserFinancialBlockRepository $repository) {}

    public function saveOrUpdateState(SaveFinancialBlockRequestDTO $requestDTO): void
    {
        $this->repository->saveOrUpdateState(
            resolve(SaveOrUpdateBlockStateRequestDTO::class)
                ->setUserId($requestDTO->getUserId())
                ->setReason($requestDTO->getReason())
                ->setRestrictedUntil($requestDTO->getRestrictedUntil())
                ->setAction($requestDTO->getAction())
        );
    }
}
