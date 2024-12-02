<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;

interface DepositRepositoryInterface
{
    public function create(CreateDepositRequestDTO $requestDTO): void;
}
