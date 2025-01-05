<?php

namespace App\Repositories;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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

    public function getAllDepositWithdraw(int $userId): Collection
    {
        return Transaction::query()
            ->with('deposit', 'withdrawal')
            ->where('user_id', $userId)
            ->where('status', TransactionStatusEnum::SUCCESS)
            ->whereIn('type', [TransactionTypeEnum::DEPOSIT, TransactionTypeEnum::WITHDRAWAL])
            ->latest('id')
            ->get();
    }
}
