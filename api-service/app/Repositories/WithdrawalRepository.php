<?php

namespace App\Repositories;

use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    public function create(CreateWithdrawalRequestDTO $requestDTO): Withdrawal
    {
        return Withdrawal::query()->create([
            'amount' => $requestDTO->getAmount(),
            'status' => $requestDTO->getStatus(),
            'user_id' => $requestDTO->getUserId(),
            'address' => $requestDTO->getAddress(),
            'fee' => $requestDTO->getFee(),
            'currency_chain' => $requestDTO->getCurrencyChain(),
            'currency_symbol' => $requestDTO->getCurrencySymbol(),
        ]);
    }
}
