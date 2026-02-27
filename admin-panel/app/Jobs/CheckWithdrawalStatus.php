<?php

namespace App\Jobs;

use App\Enums\WithdrawalStatusEnum;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Models\Withdrawal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckWithdrawalStatus implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // 30 seconds between retries
    public int $maxRetries = 10; // Maximum number of times to check status
    public int $currentRetry = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $withdrawalId,
        private readonly int $retryCount = 0
    ) {
        $this->currentRetry = $retryCount;
        $this->onQueue('admin-withdrawal-check');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $withdrawal = Withdrawal::with(['currencyChain', 'user'])->find($this->withdrawalId);

        if (!$withdrawal) {
            Log::error("Withdrawal not found for status check: {$this->withdrawalId}");
            return;
        }

        // Only check withdrawals that are in PROCESSING status
        if ($withdrawal->status !== WithdrawalStatusEnum::PROCESSING) {
            Log::info("Withdrawal {$this->withdrawalId} is not in processing status: {$withdrawal->status->value}");
            return;
        }

        try {
            $hdWalletService = resolve(HDWalletFacade::class);
            
            $responseDTO = $hdWalletService->getStatus(
                resolve(GetWithdrawalStatusRequestDTO::class)
                    ->setWithdrawalId($withdrawal->id)
                    ->setBlockchain($withdrawal->currencyChain->blockchain_name->value)
                    ->setCurrencySymbol($withdrawal->currency_symbol)
            );

            if ($responseDTO->getStatus() === 'completed') {
                $this->handleCompletedWithdrawal($withdrawal, $responseDTO);
                Log::info("Withdrawal {$this->withdrawalId} marked as completed automatically");
                
            } elseif ($responseDTO->getStatus() === 'failed') {
                $this->handleFailedWithdrawal($withdrawal, $responseDTO);
                Log::info("Withdrawal {$this->withdrawalId} marked as failed automatically");
                
            } else {
                // Status is still pending/processing, schedule another check
                $this->scheduleNextCheck();
            }

        } catch (Throwable $e) {
            Log::error("Error checking withdrawal status for {$this->withdrawalId}: " . $e->getMessage());
            
            // If we haven't reached max retries, schedule another check
            if ($this->currentRetry < $this->maxRetries) {
                $this->scheduleNextCheck();
            } else {
                Log::warning("Max retries reached for withdrawal status check: {$this->withdrawalId}");
            }
        }
    }

    /**
     * Handle completed withdrawal
     */
    private function handleCompletedWithdrawal(Withdrawal $withdrawal, $responseDTO): void
    {
        try {
            DB::beginTransaction();

            // Get wallet and update balances
            $wallet = \App\Models\Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                // Deduct from locked_balance only (balance was already deducted during creation)
                $wallet->decrement('locked_balance', $withdrawal->amount);
            }

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $responseDTO->getTransactionHash(),
                'status' => WithdrawalStatusEnum::COMPLETED,
                'hd_wallet_network_fee' => $responseDTO->getFee(),
                'confirmed_at' => now(),
                'description' => 'Withdrawal completed automatically',
            ]);

            // Create transaction record
            \App\Models\Transaction::create([
                'user_id' => $withdrawal->user_id,
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => -$withdrawal->amount,
                'balance' => $wallet->balance,
                'type' => \App\Enums\TransactionTypeEnum::WITHDRAWAL,
                'subtype' => \App\Enums\TransactionSubTypeEnum::USER_INITIATED,
                'status' => \App\Enums\TransactionStatusEnum::SUCCESS,
                'description' => 'برداشت به آدرس: ' . $withdrawal->address . ' هش تراکنش: ' . $responseDTO->getTransactionHash()
            ]);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to handle completed withdrawal {$this->withdrawalId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle failed withdrawal
     */
    private function handleFailedWithdrawal(Withdrawal $withdrawal, $responseDTO): void
    {
        try {
            DB::beginTransaction();

            // Update withdrawal status
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::FAILED,
                'description' => $responseDTO->getDescription() ?? 'Withdrawal failed',
            ]);

            // Unlock the balance
            $this->unlockBalance($withdrawal);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to handle failed withdrawal {$this->withdrawalId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Schedule the next status check
     */
    private function scheduleNextCheck(): void
    {
        if ($this->currentRetry < $this->maxRetries) {
            // Schedule next check with exponential backoff
            $delay = min(300, 60 * pow(2, $this->currentRetry)); // Max 5 minutes delay
            
            CheckWithdrawalStatus::dispatch($this->withdrawalId, $this->currentRetry + 1)
                ->delay(now()->addSeconds($delay));
                
            Log::info("Scheduled next withdrawal status check for {$this->withdrawalId} in {$delay} seconds (retry {$this->currentRetry})");
        }
    }

    /**
     * Unlock the balance when withdrawal fails
     */
    private function unlockBalance(Withdrawal $withdrawal): void
    {
        $wallet = \App\Models\Wallet::query()
            ->where('user_id', $withdrawal->user_id)
            ->where('currency_symbol', $withdrawal->currency_symbol)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            $wallet->decrement('locked_balance', $withdrawal->amount);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("CheckWithdrawalStatus job failed for withdrawal {$this->withdrawalId}", [
            'error' => $exception->getMessage(),
            'retry_count' => $this->currentRetry
        ]);
    }
}
