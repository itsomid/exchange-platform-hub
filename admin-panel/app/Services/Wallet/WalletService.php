<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Models\Wallet;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {

        try {
            DB::beginTransaction();

            $wallet = Wallet::query()
                ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                ->where('user_id', $requestDTO->getUserId())
            ->lockForUpdate()
            ->first();

            $balance = $wallet->balance;
            if ($requestDTO->getOperation() === BalanceOperationEnum::Increase) {
                $balance = bcadd($wallet->balance, $requestDTO->getAmount(), 8);
            } elseif ($requestDTO->getOperation() === BalanceOperationEnum::Decrease) {
                $balance = bcsub($wallet->balance, $requestDTO->getAmount(), 8);
            }

            Wallet::query()
                ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                ->where('user_id', $requestDTO->getUserId())
                ->update([
                    'balance' => $balance,
                ]);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }

    }
}
