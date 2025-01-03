<?php

namespace App\Repositories;

use App\Models\Deposit;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;

class DepositRepository implements DepositRepositoryInterface
{
    public function createOrUpdateDeposit(CreateDepositRequestDTO $requestDTO): void
    {
        Deposit::query()
            ->updateOrCreate([
                'address' => $requestDTO->getPublicKey(),
                'status' => $requestDTO->getStatus(),
                'user_id' => $requestDTO->getUserId(),
            ], [
                'user_id' => $requestDTO->getUserId(),
                'amount' => $requestDTO->getAmount(),
                'status' => $requestDTO->getStatus(),
                'currency_symbol' => $requestDTO->getCurrencySymbol(),
                'currency_chain' => $requestDTO->getCurrencyChain(),
                'address' => $requestDTO->getPublicKey(),
                'expiration_date' => $requestDTO->getExpirationDate(),
            ]);
    }
}
