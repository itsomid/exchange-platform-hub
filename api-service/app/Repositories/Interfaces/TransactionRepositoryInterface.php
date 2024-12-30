<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionRequestDTO $requestDTO): Transaction;
}
