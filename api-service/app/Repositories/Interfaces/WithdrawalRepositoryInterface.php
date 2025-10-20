<?php

namespace App\Repositories\Interfaces;

use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WithdrawalRepositoryInterface
{
    public function create(CreateWithdrawalRequestDTO $requestDTO): Withdrawal;

    public function getWithdrawals(int $userId, ?string $currencySymbol = null): Collection;

    public function getWithdrawalsPaginated(int $userId, ?string $currencySymbol = null, ?string $status = null, int $page = 1, int $perPage = 10): LengthAwarePaginator;

    public function getUserAllPendingWithdrawal(int $userId): Collection;

    public function getAllPending(): Collection;
}
