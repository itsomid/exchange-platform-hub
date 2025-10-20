<?php

namespace App\Repositories\Interfaces;

use App\Models\Deposit;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Deposit\CreateOrUpdatePendingDepositRequestDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepositRepositoryInterface
{
    public function createOrUpdateDeposit(CreateOrUpdatePendingDepositRequestDTO $requestDTO): void;

    public function create(CreateDepositRequestDTO $requestDTO): Deposit;

    public function getPendingDeposits(): Collection;

    public function isDepositExists(string $transactionHash): bool;

    public function getDeposits(int $userId, ?string $currencySymbol = null): Collection;

    public function getDepositsPaginated(int $userId, ?string $currencySymbol = null, ?string $status = null, int $page = 1, int $perPage = 10): LengthAwarePaginator;
}
