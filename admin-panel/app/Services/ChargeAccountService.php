<?php

namespace App\Services;

use App\DTO\UserAccount\ChargeAccountRequestDTO;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Transaction;

class ChargeAccountService
{
    public function charge(ChargeAccountRequestDTO $DTO): void
    {
        $transaction = Transaction::query()->create([
            'amount' => $DTO->getAmount(),
            'user_id' => $DTO->getUserId(),
            'user_description' => $DTO->getUserDescription(),
            'transaction_type' => TransactionTypeEnum::DEPOSIT,
        ]);

        $transaction->deposit()->create([
            'deposit_type' => $DTO->getDepositType(),
            'user_id' => $DTO->getUserId(),
            'admin_id' => $DTO->getAdminId(),
        ]);

        Account::deposit($DTO->getUserId(), $DTO->getAmount());
    }
}
