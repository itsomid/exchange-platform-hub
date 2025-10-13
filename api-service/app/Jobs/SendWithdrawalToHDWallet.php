<?php

namespace App\Jobs;

use App\Enums\WithdrawalStatusEnum;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\HDWalletWithdrawalService;
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
            Log::info("Withdrawal {$this->withdrawalId} is not in pending status: {$withdrawal->status->value}");
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
            $hdWalletService = resolve(HDWalletWithdrawalService::class);
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

            Log::info("Withdrawal {$this->withdrawalId} sent to HD Wallet successfully");

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
        $withdrawal = Withdrawal::find($this->withdrawalId);

        if ($withdrawal) {
            try {
                DB::beginTransaction();

                $withdrawal->update([
                    'status' => WithdrawalStatusEnum::FAILED,
                    'description' => 'Failed to process withdrawal after multiple attempts: ' . $exception->getMessage()
                ]);

                // Unlock the balance
                $this->unlockBalance($withdrawal);

                DB::commit();

                Log::error("Withdrawal {$this->withdrawalId} marked as failed after all retry attempts", [
                    'error' => $exception->getMessage()
                ]);

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("Failed to mark withdrawal {$this->withdrawalId} as failed", [
                    'error' => $e->getMessage()
                ]);
            }
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
            $wallet->decrement('locked_balance', $withdrawal->amount);
        }
    }
}
