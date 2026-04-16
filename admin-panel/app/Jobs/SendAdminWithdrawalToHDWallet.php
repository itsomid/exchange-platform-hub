<?php

namespace App\Jobs;

use App\Enums\WithdrawalStatusEnum;
use App\Models\Withdrawal;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Helpers\Math;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdminWithdrawalToHDWallet implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $withdrawalId)
    {
        $this->onQueue('admin-withdrawal');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting SendWithdrawalToHDWallet job for withdrawal ID: {$this->withdrawalId}");

        try {
            DB::beginTransaction();

            // Atomically lock and check the withdrawal to prevent duplicate processing
            $withdrawal = Withdrawal::with(['currencyChain', 'user'])
                ->where('id', $this->withdrawalId)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                Log::error("Withdrawal not found: {$this->withdrawalId}");
                DB::commit();
                return;
            }

            // Check if withdrawal is still in QUEUED status (prevents duplicate processing)
            if ($withdrawal->status !== WithdrawalStatusEnum::QUEUED) {
                Log::info("Withdrawal {$this->withdrawalId} is not in QUEUED status: {$withdrawal->status->value}, skipping.");
                DB::commit();
                return;
            }

            // Update status to processing
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::PROCESSING,
                'description' => 'Processing withdrawal request'
            ]);

            // Calculate received amount (amount - fees)
            $fee = Math::add($withdrawal->currencyChain->network_fee, $withdrawal->currencyChain->exchange_withdrawal_fee);
            $receivedAmount = Math::sub($withdrawal->amount, $fee);

            // Send to HD Wallet
            $hdWalletService = resolve(HDWalletFacade::class);
            $hdWalletService->withdraw(
                resolve(WithdrawRequestDTO::class)
                    ->setAmount($receivedAmount)
                    ->setWithdrawalId($withdrawal->id)
                    ->setWithdrawAddress($withdrawal->address)
                    ->setBlockchain($withdrawal->currencyChain->blockchain_name->value)
                    ->setUserId($withdrawal->user_id)
                    ->setCurrencySymbol($withdrawal->currency_symbol)
            );

            $withdrawal->update([
                'description' => 'Withdrawal request sent to HD Wallet successfully'
            ]);

            DB::commit();

            // Schedule automatic status checking
            CheckWithdrawalStatus::dispatch($this->withdrawalId)
                ->onQueue('admin-withdrawal-check')
               ->delay(now()->addMinutes(2)); // Start checking after 2 minutes

            Log::info("Withdrawal {$this->withdrawalId} sent to HD Wallet successfully and status checking scheduled");

        } catch (Throwable $e) {
            DB::rollBack();

            throw $e; // This will trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        try {
            DB::beginTransaction();

            // Atomically lock and verify status to prevent duplicate failure handling
            $withdrawal = Withdrawal::where('id', $this->withdrawalId)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                DB::commit();
                Log::error("Withdrawal not found for failure handling: {$this->withdrawalId}");
                return;
            }

            // Only handle failure if withdrawal is still in a processable state
            if (!in_array($withdrawal->status, [WithdrawalStatusEnum::QUEUED, WithdrawalStatusEnum::PROCESSING])) {
                DB::commit();
                Log::info("Withdrawal {$this->withdrawalId} already handled (status: {$withdrawal->status->value}), skipping failure handler.");
                return;
            }

            // Keep status as QUEUED so the user doesn't see a failure.
            // Admin will decide to retry or cancel via the panel.
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::QUEUED,
                'job_failed_at' => now(),
                'description' => 'Job failed: ' . $exception->getMessage(),
            ]);

            // Do NOT unlock balance here - it stays locked until admin decides

            DB::commit();

            Log::error("Withdrawal {$this->withdrawalId} job failed, kept as QUEUED for admin review", [
                'error' => $exception->getMessage()
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to update withdrawal {$this->withdrawalId} after job failure", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Unlock the balance when withdrawal fails
     */
    private function unlockBalance(Withdrawal $withdrawal): void
    {
        $lockedBalanceRepository = resolve(\App\Repositories\Interfaces\LockedBalanceRepositoryInterface::class);
        $lockedBalanceRepository->deleteWithdrawalLockedBalance($withdrawal->id);

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
        }
    }
}
