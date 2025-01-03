<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Services\Wallet\DTO\Deposit\AddPendingDepositRequestDTO;

class DepositService
{
    public function __construct(private readonly DepositRepositoryInterface $repository) {}

    public function addPendingDeposit(AddPendingDepositRequestDTO $requestDTO): void
    {

        $this->repository->createOrUpdateDeposit(
            resolve(CreateDepositRequestDTO::class)
                ->setStatus(DepositStatusEnum::Pending)
                ->setUserId($requestDTO->getUserId())
                ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                ->setCurrencyChain($requestDTO->getCurrencyChain())
                ->setPublicKey($requestDTO->getPublicKey())
                ->setExpirationDate($requestDTO->getExpirationDate())
        );
    }
}
