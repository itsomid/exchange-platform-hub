<?php

namespace App\Services\Transaction;

use App\Enums\TransactionTypeEnum;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Services\Transaction\DTO\GetAllDepositWithdrawRequestDTO;
use App\Services\Transaction\DTO\GetAllDepositWithdrawResponseDTO;

class TransactionService
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository,
    ) {}

    public function getAllDepositWithdraw(GetAllDepositWithdrawRequestDTO $requestDTO): array
    {
        if ($requestDTO->getTransactionType() === TransactionTypeEnum::DEPOSIT) {
            $lists = $this->depositRepository->getDeposits($requestDTO->getUserId(), $requestDTO->getCurrencySymbol());
        } elseif ($requestDTO->getTransactionType() === TransactionTypeEnum::WITHDRAWAL) {
            $lists = $this->withdrawalRepository->getWithdrawals($requestDTO->getUserId(), $requestDTO->getCurrencySymbol());
        } else {
            $lists = $this->depositRepository->getDeposits($requestDTO->getUserId(), $requestDTO->getCurrencySymbol());
            $withdrawalLists = $this->withdrawalRepository->getWithdrawals($requestDTO->getUserId(), $requestDTO->getCurrencySymbol());
            $lists->merge($withdrawalLists);
        }

        $lists = $lists->sortByDesc('created_at');

        return $lists->map(function (Withdrawal|Deposit $transaction) {
            if (is_a($transaction, Deposit::class)) {
                $type = TransactionTypeEnum::DEPOSIT;
            } else {
                $type = TransactionTypeEnum::WITHDRAWAL;
            }

            return resolve(GetAllDepositWithdrawResponseDTO::class)
                ->setId($transaction->id)
                ->setCurrencySymbol($transaction->currency_symbol)
                ->setAmount($transaction->amount)
                ->setStatus($transaction->status->name)
                ->setCreatedAt($transaction->created_at)
                ->setType($type)
                ->setAddress($transaction->address)
                ->setTransactionHashed($transaction->transaction_hash)
                ->setConfirmedAt($transaction->confirmed_at)
                ->setCurrencyChain($transaction->currency_chain);
        })->toArray();
    }
}
