<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;

interface DepositRepositoryInterface
{
    public function createOrUpdateDeposit(CreateDepositRequestDTO $requestDTO): void;
}
