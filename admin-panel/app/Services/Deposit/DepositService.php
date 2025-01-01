<?php

namespace App\Services\Deposit;


use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;


class DepositService
{

    public function confirmDeposit($depositId,$wallet, $amount, $transactionHash)
    {
        $deposit = Deposit::findOrFail($depositId);

        if ($deposit->status === 'confirmed') {
            throw new \Exception('Deposit is already confirmed.');
        }

        DB::beginTransaction();

        try {
            // Update deposit status
            $deposit->update([
                'amount' => $amount,
                'transaction_hash' => $transactionHash,
                'status' => 'confirmed',
                'note' => 'admin_test',
                'confirmed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->increment('balance', $amount);


            // Create the transaction record
            Transaction::create([
                'user_id' => $deposit->user->id,
                'wallet_id' => $wallet->id,
                'deposit_id' => $deposit->id,
                'amount' => $deposit->amount,
                'balance' => $wallet->balance,
                'type' => TransactionTypeEnum::DEPOSIT,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز به آدرس: ' . $deposit->address . ' هش تراکنش: ' . $transactionHash,
                'admin_description' => ''
            ]);
            DB::commit();

            return $deposit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
