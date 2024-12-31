<?php

namespace App\Services\Deposit;


use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;


class DepositService
{
    public function createDeposit(
        int     $userId,
        string  $currency_symbol,
        string  $currency_chain,
        float   $amount,
        string  $address,
        ?string $transaction_hash,
        ?string $note,
    )
    {
        DB::beginTransaction();

        try {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId, 'currency_symbol' => $currency_symbol],
                ['balance' => 0]
            );
            // Create the deposit record
            $deposit = Deposit::update([
                'user_id' => $userId,
                'currency_symbol' => $currency_symbol,
                'currency_chain' => $currency_chain,
                'amount' => $amount,
                'address' => $address,
                'transaction_hash' => $transaction_hash ?? null,
                'note' => $note ?? null,
                'status' => 'pending',
            ]);


            // Create the transaction record
            Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'balance' => $wallet->balance,
                'type' => TransactionTypeEnum::DEPOSIT,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::PENDING,
                'description' => 'واریز به آدرس:' . $address . 'هش تراکنش: ' . $transaction_hash,
                'admin_description' => ''
            ]);

            DB::commit();

            return $deposit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function confirmDeposit($depositId, $amount, $transactionHash)
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
                'confirmed_at' => now(),
            ]);

            // Update wallet balance
            $deposit->wallet->increment('balance', $amount);


            // Create the transaction record
            Transaction::create([
                'user_id' => $deposit->id,
                'wallet_id' => $deposit->wallet->id,
                'amount' => $deposit->amount,
                'balance' => $deposit->wallet->balance,
                'type' => TransactionTypeEnum::DEPOSIT,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز به آدرس:' . $deposit->address . 'هش تراکنش: ' . $transactionHash,
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
