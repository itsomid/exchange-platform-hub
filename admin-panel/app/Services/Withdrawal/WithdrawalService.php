<?php

namespace App\Services\Withdrawal;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    protected $walletService;
    protected $exchangeUserId;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
    }

    /**
     * Create a new withdrawal request.
     *
     * @param int $userId
     * @param int $walletId
     * @param string $currencyChain
     * @param string $currencySymbol
     * @param float $totalAmount
     * @param string $address
     * @param string|null $description
     * @param Carbon|null $date
     * @return Withdrawal
     * @throws \Exception
     */
    public function createWithdrawal(
        int     $userId,
        int     $walletId,
        string  $currencyChain,
        string  $currencySymbol,
        float   $totalAmount,
        string  $address,
        ?string $description = null,
        ?Carbon $date = null // Optional date parameter

    ): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the wallet
            $wallet = Wallet::findOrFail($walletId);

            // Validate wallet ownership
            if ($wallet->user_id !== $userId) {
                throw new \Exception("Wallet does not belong to the user.");
            }

            $currency = Currency::whereSymbol($currencySymbol)->first();
            $total_fee = CurrencyChain::totalWithdrawalFee($currencyChain);
            $exchangeFee = CurrencyChain::whereChain($currencyChain)->value('exchange_withdrawal_fee');
            $network_fee = CurrencyChain::whereChain($currencyChain)->value('network_fee');

            $amountReceivedByUser = $totalAmount - $total_fee;

            // Validate sufficient balance
            if ($wallet->balance < $totalAmount) {
                throw new \Exception("Insufficient balance in the wallet.");
            }

            // Deduct balance and lock funds
            $wallet->decrement('balance', $totalAmount);
            $wallet->increment('locked_balance', $totalAmount);

            // Set timestamps
            $timestamp = $date ?? now();

            // Create the withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id' => $userId,
                'currency_chain' => $currencyChain,
                'currency_symbol' => $currencySymbol,
                'amount' => $totalAmount,
                'total_fee' => $total_fee,
                'exchange_fee' => $exchangeFee,
                'network_fee' => $network_fee,
                'address' => $address,
                'status' => $totalAmount >= $currency->max_auto_withdraw_amount ? WithdrawalStatusEnum::AWAITING_APPROVAL : WithdrawalStatusEnum::PENDING,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            if ($totalAmount >= $currency->max_auto_withdraw_amount) {
                $withdrawal->update([
                    'description' => 'Admin approval required'
                ]);
            } else {
                $withdrawal->update([
                    'description' => 'Withdraw request send to HD Wallet'
                ]);
                //TODO: Send Withdraw request to HD Wallet
            }
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
     * @param int $walletId
     * @param string $transactionHash
     * @return Withdrawal
     * @throws \Exception
     */

    public function confirmWithdrawal(int $withdrawalId, int $walletId, string $transactionHash): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            $wallet = Wallet::findOrFail($walletId);

            if ($withdrawal->status !== WithdrawalStatusEnum::PENDING) {
                throw new \Exception("Withdrawal is already processed.");
            }

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $transactionHash,
                'status' => WithdrawalStatusEnum::COMPLETED,
                'confirmed_at' => now(),
                'description' => 'Withdraw Completed'
            ]);

            // Unlock funds and deduct locked balance
            $wallet->decrement('locked_balance', $withdrawal->amount + $withdrawal->total_fee);


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
                'description' => 'برداشت به آدرس: ' . $withdrawal->address . ' هش تراکنش: ' . $transactionHash,
                'admin_description' => ''
            ]);


            $this->createExchangeWithdrawalFee($withdrawal->currency_symbol,$withdrawal->currency_chain, $withdrawal);


            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function adminApproveWithdrawal(int $withdrawalId, int $admin_id): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->status !== WithdrawalStatusEnum::AWAITING_APPROVAL) {
                throw new \Exception("This withdrawal has already been processed.");
            }

            // Update withdrawal status to 'approved'
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::PENDING,
                'admin_id' => $admin_id, // Store which admin approved the withdrawal
                'description' => 'Withdraw request send to HD Wallet by admin (#' . $admin_id . ')'
            ]);


            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function adminCancelWithdrawal(int $withdrawalId, int $admin_id): Withdrawal
    {
        DB::beginTransaction();

        try {
            // Fetch the withdrawal record
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if ($withdrawal->status !== WithdrawalStatusEnum::AWAITING_APPROVAL) {
                throw new \Exception("This withdrawal has already been processed.");
            }

            // Update withdrawal status to 'approved'
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::FAILED,
                'admin_id' => $admin_id, // Store which admin Canceled the withdrawal
                'description' => 'Withdraw canceled by admin (#' . $admin_id . ')'
            ]);

            $withdrawal->wallet->decrement('locked_balance', $withdrawal->amount + $withdrawal->total_fee);


            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($currency_symbol,$currency_chain, $withdrawal): void
    {
        try {

            $exchangeWallet = $this->walletService->getExchangeWallet($currency_symbol);

            $exchangeWithdrawalFee = CurrencyChain::whereChain($currency_chain)->value('exchange_withdrawal_fee');
            if ($exchangeWithdrawalFee > 0){
                Transaction::query()->create([
                    'user_id' => $this->exchangeUserId,
                    'wallet_id' => $exchangeWallet->id,
                    'withdrawal_id' => $withdrawal->id,
                    'balance' => $exchangeWallet->balance,
                    'amount' => $exchangeWithdrawalFee,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::WITHDRAWAL_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "کارمزد برداشت صرافی  {$exchangeWallet->currency_symbol} کاربر  " . "(#{$withdrawal->user->id}) ". $withdrawal->user->username ,
                ]);

                $exchangeWallet->increment('balance',$exchangeWithdrawalFee);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
