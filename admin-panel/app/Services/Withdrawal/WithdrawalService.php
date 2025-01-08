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
     * @param float $amount
     * @param string $address
     * @param string|null description
     * @return Withdrawal
     * @throws \Exception
     */
    public function createWithdrawal(
        int     $userId,
        int     $walletId,
        string  $currencyChain,
        string  $currencySymbol,
        float   $amount,
        string  $address,
        ?string $description = null
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
            $fee = CurrencyChain::totalWithdrawalFee($currencyChain);

            // Validate sufficient balance
            $totalAmount = $amount - $fee;
            if ($wallet->balance < $amount) {
                throw new \Exception("Insufficient balance in the wallet.");
            }

            // Deduct balance and lock funds
            $wallet->decrement('balance', $amount);
            $wallet->increment('locked_balance', $amount);


            // Create the withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id' => $userId,
                'currency_chain' => $currencyChain,
                'currency_symbol' => $currencySymbol,
                'amount' => $amount,
                'fee' => $fee,
                'address' => $address,
                'status' => $amount >= $currency->max_auto_withdraw_amount ? WithdrawalStatusEnum::AWAITING_APPROVAL : WithdrawalStatusEnum::PENDING,
            ]);
            if ($amount >= $currency->max_auto_withdraw_amount) {
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
            $wallet->decrement('locked_balance', $withdrawal->amount + $withdrawal->fee);


            // Create the transaction record
            Transaction::create([
                'user_id' => $withdrawal->user->id,
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => -$withdrawal->amount + $withdrawal->fee,
                'balance' => $wallet->balance,
                'type' => TransactionTypeEnum::WITHDRAWAL,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز به آدرس: ' . $withdrawal->address . ' هش تراکنش: ' . $transactionHash,
                'admin_description' => ''
            ]);


            $this->createExchangeWithdrawalFee($withdrawal->currency_symbol,$withdrawal->currency_chain, $withdrawal->id);

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

            $withdrawal->wallet->decrement('locked_balance', $withdrawal->amount + $withdrawal->fee);


            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($currency_symbol,$currency_chain, $withdrawalId): void
    {
        try {

            $exchangeWallet = $this->walletService->getExchangeWallet($currency_symbol);

            $exchangeWithdrawalFee = CurrencyChain::whereChain($currency_chain)->value('exchange_withdrawal_fee');
            if ($exchangeWithdrawalFee > 0){
                Transaction::query()->create([
                    'user_id' => $this->exchangeUserId,
                    'wallet_id' => $exchangeWallet->id,
                    'withdrawal_id' => $withdrawalId,
                    'balance' => $exchangeWallet->balance,
                    'amount' => $exchangeWithdrawalFee,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::WITHDRAWAL_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "کارمزد برداشت  {$exchangeWallet->currency_symbol} به ارزش  " . formatNumberTrimZeros($exchangeWithdrawalFee),
                ]);

                $exchangeWallet->increment('balance',$exchangeWithdrawalFee);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
