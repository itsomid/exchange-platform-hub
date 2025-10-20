<?php

namespace App\Repositories;

use App\Enums\WithdrawalStatusEnum;
use App\Helpers\Math;
use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
            'total_fee' => Math::add($requestDTO->getExchangeFee(), $requestDTO->getNetworkFee()),
            'currency_chain_id' => $requestDTO->getCurrencyChainId(),
            'currency_symbol' => $requestDTO->getCurrencySymbol(),
            'remark' => $requestDTO->getRemark(),
        ]);
    }

    public function getWithdrawals(int $userId, ?string $currencySymbol = null): Collection
    {
        return Withdrawal::query()
            ->with('currencyChain')
            ->where('user_id', $userId)
            ->when(! empty($currencySymbol), fn ($q) => $q->where('currency_symbol', $currencySymbol))
            ->latest()
            ->get();
    }

    public function getWithdrawalsPaginated(int $userId, ?string $currencySymbol = null, ?string $status = null, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $query = Withdrawal::query()
            ->with('currencyChain')
            ->where('user_id', $userId)
            ->when(! empty($currencySymbol), fn ($q) => $q->where('currency_symbol', $currencySymbol))
            ->when(! empty($status), fn ($q) => $q->where('status', $status))
            ->latest();

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getUserAllPendingWithdrawal(int $userId): Collection
    {
        return Withdrawal::query()
            ->with('currencyChain', 'user')
            ->where('user_id', $userId)
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->get();
    }

    public function getAllPending(): Collection
    {
        return Withdrawal::query()
            ->with('currencyChain', 'user')
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->get();
    }
}
