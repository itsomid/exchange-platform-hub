<?php

namespace App\Repositories\Interfaces;

use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface WithdrawalRepositoryInterface
{
    public function create(CreateWithdrawalRequestDTO $requestDTO): Withdrawal;

    public function getWithdrawals(int $userId, ?string $currencySymbol = null): Collection;

    public function getAllPending(): Collection;
}
