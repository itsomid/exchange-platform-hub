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
            'deposit_id' => $requestDTO->getDepositId(),
            'withdrawal_id' => $requestDTO->getWithdrawalId(),
            'otc_order_id' => $requestDTO->getOtcOrderId(),
            'spot_trade_id' => $requestDTO->getSpotTradeId(),
            'stock_contract_id' => $requestDTO->getStockContractId(),
            'balance' => $requestDTO->getBalance(),
            'amount' => $requestDTO->getAmount(),
            'coin_price' => $requestDTO->getCoinPrice(),
            'exchange_id' => $requestDTO->getExchangeId(),
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

        if ($currencySymbol) {
            $transactions->where(function ($q) use ($transactionType, $currencySymbol) {
                if (in_array(TransactionTypeEnum::DEPOSIT, $transactionType)) {
                    $q->whereHas('deposit', fn(Builder $q) => $q->where('currency_symbol', $currencySymbol));
                }
                if (in_array(TransactionTypeEnum::WITHDRAWAL, $transactionType)) {
                    if (in_array(TransactionTypeEnum::DEPOSIT, $transactionType)) {
                        $q->orWhereHas('withdrawal', fn(Builder $q) => $q->where('currency_symbol', $currencySymbol));
                    } else {
                        $q->WhereHas('withdrawal', fn(Builder $q) => $q->where('currency_symbol', $currencySymbol));
                    }
                }
            });
        } else {
            $transactions->where(function ($q) {
                $q->has('deposit')->orHas('withdrawal');
            });
        }

        return $transactions->get();
    }
}
