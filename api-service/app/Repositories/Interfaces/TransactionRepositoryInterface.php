<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionRequestDTO $requestDTO): Transaction;

    public function getAllDepositWithdraw(int $userId): Collection;
}
