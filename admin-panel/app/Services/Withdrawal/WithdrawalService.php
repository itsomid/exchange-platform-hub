<?php

namespace App\Services\Withdrawal;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Models\CurrencyChain;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    /**
     * Create a new withdrawal request.
     *
     * @param int $userId
     * @param int $walletId
     * @param string $currencyChain
     * @param string $currencySymbol
     * @param float $amount
     * @param string $address
     * @param float $fee
     * @param string|null description
     * @return Withdrawal
     * @throws \Exception
     */
    public function createWithdrawal(
        int $userId,
        int $walletId,
        string $currencyChain,
        string $currencySymbol,
        float $amount,
        string $address,
        ?string $description = null
    ): Withdrawal {
        DB::beginTransaction();

        try {
            // Fetch the wallet
            $wallet = Wallet::findOrFail($walletId);

            // Validate wallet ownership
            if ($wallet->user_id !== $userId) {
                throw new \Exception("Wallet does not belong to the user.");
            }

            $fee = CurrencyChain::where('chain',$currencyChain)->first()->total_withdrawal_fee;
            // Validate sufficient balance
            $totalAmount = $amount + $fee;
            if ($wallet->balance < $totalAmount) {
                throw new \Exception("Insufficient balance in the wallet.");
            }

            // Deduct balance and lock funds
            $wallet->decrement('balance', $totalAmount);
            $wallet->increment('locked_balance', $totalAmount);

            // Create the withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id' => $userId,
                'currency_chain' => $currencyChain,
                'currency_symbol' => $currencySymbol,
                'amount' => $amount,
                'fee' => $fee,
                'address' => $address,
                'status' => WithdrawalStatusEnum::PENDING,
                'description' => $description,
            ]);

            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Confirm a withdrawal request.
     *
     * @param int $withdrawalId
     * @param string $transactionHash
     * @return Withdrawal
     * @throws \Exception
     */

    public function confirmWithdrawal(int $withdrawalId, string $transactionHash): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->status !== 'pending') {
                throw new \Exception("Withdrawal is already processed.");
            }

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $transactionHash,
                'status' => WithdrawalStatusEnum::COMPLETED,
                'confirmed_at' => now(),
            ]);

            // Unlock funds and deduct locked balance
            $wallet = $withdrawal->wallet;
            $wallet->decrement('locked_balance', $withdrawal->amount + $withdrawal->fee);

            // Create the transaction record
            Transaction::create([
                'user_id' => $withdrawal->user->id,
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => -$withdrawal->amount,
                'balance' => $wallet->balance,
                'type' => TransactionTypeEnum::WITHDRAWAL,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز به آدرس: ' . $withdrawal->address . ' هش تراکنش: ' . $transactionHash,
                'admin_description' => ''
            ]);


            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function adminConfirmWithdrawal(int $withdrawalId, int $adminId): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->status !== WithdrawalStatusEnum::PENDING) {
                throw new \Exception("This withdrawal has already been processed.");
            }

            // Update withdrawal status to 'approved'
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::COMPLETED,
                'admin_id' => $adminId, // Store which admin approved the withdrawal
                'confirmed_at' => now(),
            ]);

            // Create an admin confirmation transaction log
            Transaction::create([
                'user_id' => $withdrawal->user_id,
                'wallet_id' => $withdrawal->wallet_id,
                'amount' => -$withdrawal->amount,
                'balance' => $withdrawal->wallet->balance,
                'type' => 'withdrawal',
                'subtype' => 'admin_approved',
                'status' => 'approved',
                'description' => "Admin approved withdrawal to address: {$withdrawal->address}",
                'admin_description' => "Approved by Admin ID: {$adminId}",
            ]);

            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function adminCancelWithdrawal(int $withdrawalId, int $adminId): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->status !== WithdrawalStatusEnum::PENDING) {
                throw new \Exception("This withdrawal has already been processed.");
            }

            // Update withdrawal status to 'approved'
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::FAILED,
                'admin_id' => $adminId, // Store which admin Canceled the withdrawal
                'description' => 'Withdraw canceled by admin (#' .$adminId .')'
            ]);



            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
