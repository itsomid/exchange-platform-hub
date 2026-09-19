<?php

namespace App\Jobs;

use App\Enums\WithdrawalStatusEnum;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Models\Withdrawal;
use App\Helpers\Math;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWithdrawalToHDWallet implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $withdrawalId)
    {
        $this->onQueue('api-withdrawal');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $withdrawal = Withdrawal::with(['currencyChain', 'user'])->find($this->withdrawalId);

        if (!$withdrawal) {
            Log::error("Withdrawal not found: {$this->withdrawalId}");
            return;
        }

        // Check if withdrawal is still in pending status
        if ($withdrawal->status !== WithdrawalStatusEnum::QUEUED) {
            Log::info("Withdrawal {$this->withdrawalId} is not in queued status: {$withdrawal->status->value}");
            return;
        }

        try {
            DB::beginTransaction();
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
                    ->setBlockchain($withdrawal->currencyChain->blockchain_name)
                    ->setUserId($withdrawal->user_id)
                    ->setCurrencySymbol($withdrawal->currency_symbol)
            );

            $withdrawal->update([
                'description' => 'Withdrawal request sent to HD Wallet successfully'
            ]);

            DB::commit();

            // Schedule automatic status checking
            CheckWithdrawalStatus::dispatch($this->withdrawalId)
                ->onQueue('api-withdrawal-check')
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

            $withdrawal = Withdrawal::where('id', $this->withdrawalId)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                DB::commit();
                Log::error("Withdrawal not found for failure handling: {$this->withdrawalId}");
                return;
            }

            if (!in_array($withdrawal->status, [WithdrawalStatusEnum::QUEUED, WithdrawalStatusEnum::PROCESSING])) {
                DB::commit();
                Log::info("Withdrawal {$this->withdrawalId} already handled (status: {$withdrawal->status->value}), skipping failure handler.");
                return;
            }

            // Keep status as QUEUED so the user doesn't see a failure.
            $withdrawal->update([
                'status' => WithdrawalStatusEnum::QUEUED,
                'job_failed_at' => now(),
                'description' => 'Job failed: ' . $exception->getMessage(),
            ]);

            // Do NOT unlock balance here - it stays locked until resolved

            DB::commit();

            Log::error("Withdrawal {$this->withdrawalId} job failed, kept as QUEUED for review", [
                'error' => $exception->getMessage()
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to update withdrawal {$this->withdrawalId} after job failure", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
