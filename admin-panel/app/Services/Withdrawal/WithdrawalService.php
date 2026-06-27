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
use App\Models\LockedBalanceDetail;
use App\Models\Transaction;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use App\Services\Wallet\DTO\Withdrawal\CheckWithdrawalResponseDTO;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\LockedBalanceTypeEnum;
use App\Jobs\SendAdminWithdrawalToHDWallet;
use Throwable;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    protected $walletService;
    protected $bitexroomUserId;
    private HDWalletFacade $hdWalletWithdrawalService;

    public function __construct(WalletService $walletService, HDWalletFacade $hdWalletWithdrawalService)
    {
        $this->walletService = $walletService;
        $this->hdWalletWithdrawalService = $hdWalletWithdrawalService;
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
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
        int $userId,
        int $walletId,
        string $currencyChain,
        string $currencySymbol,
        float $totalAmount,
        string $address,
        ?string $description = null,
        ?Carbon $date = null, // Optional date parameter
    ): Withdrawal {
        DB::beginTransaction();

        try {
            // Fetch the wallet
            $wallet = Wallet::findOrFail($walletId);

            // Validate wallet ownership
            if ($wallet->user_id !== $userId) {
                throw new \Exception('Wallet does not belong to the user.');
            }

            $currency = Currency::whereSymbol($currencySymbol)->first();
            $total_fee = CurrencyChain::totalWithdrawalFee($currencyChain);
            $exchangeFee = CurrencyChain::whereChain($currencyChain)->value('exchange_withdrawal_fee');
            $network_fee = CurrencyChain::whereChain($currencyChain)->value('network_fee');

            $value_in_usdt = $currency->exchangePrice * $totalAmount;

            $amountReceivedByUser = $totalAmount - $total_fee;

            // Validate sufficient available balance (balance - locked_balance)
            if (($wallet->balance - $wallet->locked_balance) < $totalAmount) {
                throw new \Exception('Insufficient balance in the wallet.');
            }

            // Set timestamps
            $timestamp = $date ?? now();

            // Create the withdrawal record first (needed for locked_balance_details FK)
            $withdrawal = Withdrawal::create([
                'user_id' => $userId,
                'currency_chain' => $currencyChain,
                'currency_symbol' => $currencySymbol,
                'amount' => $totalAmount,
                'total_fee' => $total_fee,
                'exchange_fee' => $exchangeFee,
                'network_fee' => $network_fee,
                'usdt_value' => $value_in_usdt,
                'address' => $address,
                'status' => $totalAmount >= $currency->max_auto_withdraw_amount ? WithdrawalStatusEnum::AWAITING_APPROVAL : WithdrawalStatusEnum::PENDING,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            // Lock funds (deduct-at-completion model: balance is unchanged until withdrawal completes)
            $wallet->increment('locked_balance', $totalAmount);
            LockedBalanceDetail::create([
                'wallet_id' => $wallet->id,
                'amount' => $totalAmount,
                'type' => LockedBalanceTypeEnum::WITHDRAWAL,
                'withdrawal_id' => $withdrawal->id,
                'description' => 'مسدود سازی دارایی بابت برداشت #' . $withdrawal->id,
            ]);

            if ($totalAmount >= $currency->max_auto_withdraw_amount) {
                $withdrawal->update([
                    'description' => 'Admin approval required',
                ]);
            } else {
                $withdrawal->update([
                    'description' => 'Withdraw request send to HD Wallet',
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

            if (! in_array($withdrawal->status, [
                WithdrawalStatusEnum::PENDING,
                WithdrawalStatusEnum::PROCESSING,
                WithdrawalStatusEnum::FAILED,
            ])) {
                throw new \Exception('Withdrawal is already processed.');
            }

            if ($wallet->balance < $withdrawal->amount) {
                throw new \Exception('Insufficient balance to complete withdrawal.');
            }

            // Deduct balance now (deduct-at-completion model)
            $wallet->decrement('balance', $withdrawal->amount);

            // Release lock if it still exists (not present when withdrawal was previously FAILED)
            $hasLockedDetail = LockedBalanceDetail::where('withdrawal_id', $withdrawal->id)->exists();
            if ($hasLockedDetail) {
                $wallet->decrement('locked_balance', $withdrawal->amount);
                LockedBalanceDetail::where('withdrawal_id', $withdrawal->id)->delete();
            }

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $transactionHash,
                'status' => WithdrawalStatusEnum::COMPLETED,
                'confirmed_at' => now(),
                'description' => 'Withdraw Completed',
            ]);

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
                'admin_description' => '',
            ]);

            $this->createExchangeWithdrawalFee($withdrawal->currency_symbol, $withdrawal->currency_chain, $withdrawal);

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
                throw new \Exception('This withdrawal has already been processed.');
            }

            // Update withdrawal status to queued and dispatch job
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::QUEUED,
                'admin_id' => $admin_id, // Store which admin approved the withdrawal
                'description' => 'Withdrawal approved by admin (#' . $admin_id . ') and queued for processing',
            ]);

            // Dispatch job to process withdrawal
            SendAdminWithdrawalToHDWallet::dispatch($withdrawal->id);

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
                throw new \Exception('This withdrawal has already been processed.');
            }

            // Update withdrawal status to 'approved'
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::REJECTED,
                'admin_id' => $admin_id, // Store which admin Canceled the withdrawal
                'description' => 'Withdraw canceled by admin (#' . $admin_id . ')',
            ]);

            $wallet = \App\Models\Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                $amountToUnlock = min($wallet->locked_balance, $withdrawal->amount);
                if ($amountToUnlock > 0) {
                    $wallet->decrement('locked_balance', $amountToUnlock);
                }
                LockedBalanceDetail::where('withdrawal_id', $withdrawal->id)->delete();
            }

            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a queued/failed withdrawal by admin and restore user balance.
     */
    public function adminCancelQueuedWithdrawal(int $withdrawalId, int $admin_id, string $reason): Withdrawal
    {
        DB::beginTransaction();

        try {
            $withdrawal = Withdrawal::where('id', $withdrawalId)->lockForUpdate()->firstOrFail();

            if (!in_array($withdrawal->status, [WithdrawalStatusEnum::QUEUED])) {
                throw new \Exception('This withdrawal is not in a cancellable state.');
            }

            $withdrawal->update([
                'status' => WithdrawalStatusEnum::REJECTED,
                'admin_id' => $admin_id,
                'description' => 'Cancelled by admin (#' . $admin_id . '): ' . $reason,
            ]);

            $wallet = \App\Models\Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                // Release locked funds (balance was never deducted, only locked)
                $amountToUnlock = min($wallet->locked_balance, $withdrawal->amount);
                if ($amountToUnlock > 0) {
                    $wallet->decrement('locked_balance', $amountToUnlock);
                }
                LockedBalanceDetail::where('withdrawal_id', $withdrawal->id)->delete();
            }

            DB::commit();

            return $withdrawal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($currency_symbol, $currency_chain, $withdrawal): void
    {
        try {
            $exchangeWallet = $this->walletService->getExchangeWallet($currency_symbol);

            $exchangeWithdrawalFee = CurrencyChain::whereChain($currency_chain)->value('exchange_withdrawal_fee');
            if ($exchangeWithdrawalFee > 0) {
                Transaction::query()->create([
                    'user_id' => $this->bitexroomUserId,
                    'wallet_id' => $exchangeWallet->id,
                    'withdrawal_id' => $withdrawal->id,
                    'balance' => $exchangeWallet->balance,
                    'amount' => $exchangeWithdrawalFee,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => "کارمزد برداشت صرافی  {$exchangeWallet->currency_symbol} کاربر  " . "(#{$withdrawal->user->id}) " . $withdrawal->user->username,
                ]);

                $exchangeWallet->increment('balance', $exchangeWithdrawalFee);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    ///report////

    public function totalWithdrawalValueBasedCurrency(string $currencySymbol, int $userId): float
    {
        // Get the total amount of deposits for the given currency
        return $totalWithdrawalValue = Withdrawal::whereUserId($userId)->where('currency_symbol', $currencySymbol)->sum('usdt_value');
    }

    /**
     * Check withdrawal status with HDWallet service
     *
     * @param Collection $pendingWithdrawal
     * @return CheckWithdrawalResponseDTO
     */
    public function checkWithdrawal(Collection $pendingWithdrawal): CheckWithdrawalResponseDTO
    {
        $checkWithdrawalResponseDTO = resolve(CheckWithdrawalResponseDTO::class);

        foreach ($pendingWithdrawal as $withdrawal) {

            try {

                $responseDTO = $this->hdWalletWithdrawalService->getStatus(
                    resolve(GetWithdrawalStatusRequestDTO::class)
                        ->setWithdrawalId($withdrawal->id)
                        ->setBlockchain($withdrawal->currencyChain->blockchain_name)
                        ->setCurrencySymbol($withdrawal->currency_symbol)
                );

                $checkWithdrawalResponseDTO
                    ->setWithdrawId($withdrawal->id)
                    ->setCurrencyChain($withdrawal->currencyChain->chain->value)
                    ->setWalletAddress($withdrawal->address)
                    ->setAmount($withdrawal->amount)
                    ->setTotalFee($withdrawal->total_fee)
                    ->setCurrencySymbol($withdrawal->currency_symbol)
                    ->setExplorerAddressUrl($withdrawal->explorer_address_url)
                    ->setExplorerTxUrl($withdrawal->explorer_tx_url);

                if ($responseDTO->getStatus() === 'failed') {

                    DB::beginTransaction();
                    try {
                        $lockedWithdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->first();
                        if ($lockedWithdrawal && $lockedWithdrawal->status !== WithdrawalStatusEnum::FAILED) {
                            $lockedWithdrawal->update([
                                'status' => WithdrawalStatusEnum::FAILED,
                                'description' => $responseDTO->getDescription()
                            ]);

                            $failedWallet = Wallet::query()
                                ->where('user_id', $lockedWithdrawal->user_id)
                                ->where('currency_symbol', $lockedWithdrawal->currency_symbol)
                                ->lockForUpdate()
                                ->first();

                            if ($failedWallet) {
                                // Release locked funds (balance was never deducted, only locked)
                                $amountToUnlock = min($failedWallet->locked_balance, $lockedWithdrawal->amount);
                                if ($amountToUnlock > 0) {
                                    $failedWallet->decrement('locked_balance', $amountToUnlock);
                                }
                                LockedBalanceDetail::where('withdrawal_id', $lockedWithdrawal->id)->delete();
                            }
                        }
                        DB::commit();
                    } catch (Throwable $e) {
                        DB::rollBack();
                        throw $e;
                    }

                    $checkWithdrawalResponseDTO->setStatus(WithdrawalStatusEnum::FAILED);
                } elseif ($responseDTO->getStatus() === 'completed') {
                    $wallet = Wallet::query()
                        ->where('user_id', $withdrawal->user_id)
                        ->where('currency_symbol', $withdrawal->currency_symbol)
                        ->firstOrFail();

                    $this->confirmWithdrawal($withdrawal->id, $wallet->id, $responseDTO->getTransactionHash());

                    $checkWithdrawalResponseDTO
                        ->setStatus(WithdrawalStatusEnum::COMPLETED)
                        ->setTransactionHash($responseDTO->getTransactionHash())
                        ->setConfirmedAt(now());
                }
            } catch (NotFoundException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                report($exception);
                // Log error but continue processing other withdrawals
                continue;
            }
        }

        return $checkWithdrawalResponseDTO;
    }
}
