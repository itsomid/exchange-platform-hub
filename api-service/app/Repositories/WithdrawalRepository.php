<?php

namespace App\Repositories;

use App\Enums\WithdrawalStatusEnum;
use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    public function create(CreateWithdrawalRequestDTO $requestDTO): Withdrawal
    {
        return Withdrawal::query()->create([
            'amount' => $requestDTO->getAmount(),
            'usdt_value' => $requestDTO->getUSDTValue(),
            'status' => $requestDTO->getStatus(),
            'user_id' => $requestDTO->getUserId(),
            'address' => $requestDTO->getAddress(),
            'network_fee' => $requestDTO->getNetworkFee(),
            'exchange_fee' => $requestDTO->getExchangeFee(),
            'total_fee' => bcadd(toDecimalString($requestDTO->getExchangeFee()), toDecimalString($requestDTO->getNetworkFee()), 8),
            'currency_chain_id' => $requestDTO->getCurrencyChainId(),
            'currency_symbol' => $requestDTO->getCurrencySymbol(),
        ]);
    }

    public function getWithdrawals(int $userId, ?string $currencySymbol = null): Collection
    {
        return Withdrawal::query()
            ->where('user_id', $userId)
            ->when(! empty($currencySymbol), fn ($q) => $q->where('currency_symbol', $currencySymbol))
            ->latest()
            ->get();
    }

    public function getAllPending(int $userId): Collection
    {
        return Withdrawal::query()
            ->with('currency.chains', 'user')
            ->where('user_id', $userId)
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->get();
    }
}
