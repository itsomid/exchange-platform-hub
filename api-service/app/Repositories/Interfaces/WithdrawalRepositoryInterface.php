<?php

namespace App\Repositories\Interfaces;

use App\Models\Withdrawal;
use App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO;

interface WithdrawalRepositoryInterface
{
    public function create(CreateWithdrawalRequestDTO $requestDTO): Withdrawal;
}
