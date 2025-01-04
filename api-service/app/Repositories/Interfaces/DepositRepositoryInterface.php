<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\DTO\Deposit\CreateOrUpdatePendingDepositRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface DepositRepositoryInterface
{
    public function createOrUpdateDeposit(CreateOrUpdatePendingDepositRequestDTO $requestDTO): void;

    public function create(CreateDepositRequestDTO $requestDTO): void;

    public function getPendingDeposits(): Collection;

    public function isDepositExists(string $transactionHash): bool;
}
