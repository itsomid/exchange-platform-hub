<?php

namespace App\Repositories;

use App\Models\Deposit;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;

class DepositRepository implements DepositRepositoryInterface
{
    public function create(CreateDepositRequestDTO $requestDTO): void
    {
        Deposit::query()
            ->create([
                'user_id' => $requestDTO->getUserId(),
                'amount' => $requestDTO->getAmount(),
                'status' => $requestDTO->getStatus(),
                'currency_symbol' => $requestDTO->getCurrencySymbol(),
                'currency_chain' => $requestDTO->getCurrencyChain(),
                'public_key' => $requestDTO->getPublicKey(),
            ]);
    }
}
