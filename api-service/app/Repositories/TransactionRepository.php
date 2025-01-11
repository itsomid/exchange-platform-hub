<?php

namespace App\Repositories;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function getAllDepositWithdraw(int $userId, ?TransactionTypeEnum $transactionType = null, ?string $currencySymbol = null): Collection
    {
        if (is_null($transactionType)) {
            $transactionType = [TransactionTypeEnum::DEPOSIT, TransactionTypeEnum::WITHDRAWAL];
        } else {
            $transactionType = [$transactionType];
        }

        $transactions = Transaction::query()
            ->with('deposit', 'withdrawal')
            ->where('user_id', $userId)
            ->where('status', TransactionStatusEnum::SUCCESS)
            ->whereIn('type', $transactionType)
            ->latest('id');

        $transactions->when($currencySymbol && in_array(TransactionTypeEnum::DEPOSIT, $transactionType), function (Builder $q) use ($currencySymbol) {
            $q->whereHas('deposit', fn (Builder $q) => $q->where('currency_symbol', $currencySymbol));
        });

        $transactions->when($currencySymbol && in_array(TransactionTypeEnum::WITHDRAWAL, $transactionType), function (Builder $q) use ($currencySymbol) {
            $q->whereHas('withdrawal', fn (Builder $q) => $q->where('currency_symbol', $currencySymbol));
        });

        return $transactions->get();
    }
}
