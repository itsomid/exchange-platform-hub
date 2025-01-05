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
            $currency = $transaction->{$relation}->currency_symbol;
            $createdAt = $transaction->{$relation}->created_at;

            return resolve(GetAllDepositWithdrawResponseDTO::class)
                ->setCurrencySymbol($currency)
                ->setAmount($transaction->amount)
                ->setStatus($transaction->status)
                ->setCreatedAt($createdAt)
                ->setType($transaction->type);
        })->toArray();
    }
}
