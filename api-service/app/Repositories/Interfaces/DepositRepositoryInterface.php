<?php

namespace App\Repositories\Interfaces;

use App\Models\Deposit;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Deposit\CreateOrUpdatePendingDepositRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface DepositRepositoryInterface
{
    public function createOrUpdateDeposit(CreateOrUpdatePendingDepositRequestDTO $requestDTO): void;

    public function create(CreateDepositRequestDTO $requestDTO): Deposit;

    public function getPendingDeposits(): Collection;

    public function isDepositExists(string $transactionHash): bool;
}
