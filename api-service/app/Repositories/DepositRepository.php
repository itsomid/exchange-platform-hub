<?php

namespace App\Repositories;

use App\Enums\DepositStatusEnum;
use App\Models\Deposit;
use App\Repositories\DTO\Deposit\CreateOrUpdatePendingDepositRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepositRepository implements DepositRepositoryInterface
{
    public function findByTransactionHash(string $transactionHash): ?Deposit
    {
        return Deposit::query()
            ->where('transaction_hash', $transactionHash)
            ->first();
    }

    public function createOrUpdateDeposit(CreateOrUpdatePendingDepositRequestDTO $requestDTO): void
    {
        Deposit::query()
            ->updateOrCreate([
                'address' => $requestDTO->getPublicKey(),
                'status' => $requestDTO->getStatus(),
                'user_id' => $requestDTO->getUserId(),
            ], [
                'user_id' => $requestDTO->getUserId(),
                'amount' => $requestDTO->getAmount(),
                'status' => $requestDTO->getStatus(),
                'currency_symbol' => $requestDTO->getCurrencySymbol(),
                'currency_chain' => $requestDTO->getCurrencyChain(),
                'address' => $requestDTO->getPublicKey(),
                'expiration_date' => $requestDTO->getExpirationDate(),
            ]);
    }

    public function getPendingDeposits(): Collection
    {
        return Deposit::query()
            ->with('currencyChain')
            ->where('status', DepositStatusEnum::PENDING)
            ->where('expiration_date', '>', now())
            ->get();
    }

    public function create(DTO\Deposit\CreateDepositRequestDTO $requestDTO): Deposit
    {
        return Deposit::query()
            ->create([
                'user_id' => $requestDTO->getUserId(),
                'currency_symbol' => $requestDTO->getCurrencySymbol(),
                'currency_chain_id' => $requestDTO->getCurrencyChainId(),
                'amount' => $requestDTO->getAmount(),
                'address' => $requestDTO->getAddress(),
                'transaction_hash' => $requestDTO->getTransactionHash(),
                'confirmed_at' => $requestDTO->getConfirmedAt(),
                'expiration_date' => $requestDTO->getExpirationDate(),
                'status' => $requestDTO->getStatus(),
                'usdt_value' => $requestDTO->getUsdtValue(),
            ]);
    }

    public function isDepositExists(string $transactionHash): bool
    {
        return Deposit::query()
            ->where('transaction_hash', $transactionHash)
            ->exists();
    }

    public function getDeposits(int $userId, ?string $currencySymbol = null): Collection
    {
        return Deposit::query()
            ->with('currencyChain')
            ->where('user_id', $userId)
            ->when(! empty($currencySymbol), fn ($q) => $q->where('currency_symbol', $currencySymbol))
            ->latest()
            ->get();
    }

    public function getDepositsPaginated(int $userId, ?string $currencySymbol = null, ?string $status = null, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $query = Deposit::query()
            ->with(['currencyChain', 'currency'])
            ->where('user_id', $userId)
            ->when(!empty($currencySymbol), fn($q) => $q->where('currency_symbol', $currencySymbol))
            ->when(!empty($status), fn($q) => $q->whereRaw('LOWER(status) = ?', [strtolower($status)]))
            ->latest();

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
