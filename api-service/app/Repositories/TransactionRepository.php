<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(CreateTransactionRequestDTO $requestDTO): Transaction
    {
        return Transaction::query()->create([
            'user_id' => $requestDTO->getUserId(),
            'wallet_id' => $requestDTO->getWalletId(),
            'otc_order_id' => $requestDTO->getOtcOrderId(),
            'balance' => $requestDTO->getBalance(),
            'amount' => $requestDTO->getAmount(),
            'type' => $requestDTO->getType(),
            'subtype' => $requestDTO->getSubtype(),
            'status' => $requestDTO->getStatus(),
            'description' => $requestDTO->getDescription(),
        ]);
    }
}
