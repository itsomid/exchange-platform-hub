<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Transaction\DTO\GetAllDepositWithdrawRequestDTO;
use App\Services\Transaction\DTO\GetAllDepositWithdrawResponseDTO;

class TransactionService
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository) {}

    public function getAllDepositWithdraw(GetAllDepositWithdrawRequestDTO $requestDTO): array
    {
        $lists = $this->transactionRepository->getAllDepositWithdraw($requestDTO->getUserId());

        return $lists->map(function (Transaction $transaction) {
            $relation = $transaction->relationLoaded('deposit') ? 'deposit' : 'withdrawal';
            $relation = $transaction->{$relation};

            return resolve(GetAllDepositWithdrawResponseDTO::class)
                ->setCurrencySymbol($relation->currency_symbol)
                ->setAmount($transaction->amount)
                ->setStatus($transaction->status)
                ->setCreatedAt($relation->created_at)
                ->setType($transaction->type)
                ->setAddress($relation->address)
                ->setTransactionHashed($relation->transaction_hash)
                ->setConfirmedAt($relation->confirmed_at);
        })->toArray();
    }
}
