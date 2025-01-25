<?php

namespace App\Jobs;

use App\Services\Wallet\CheckWalletService;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckUserDepositJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $userId, private readonly string $currencySymbol, private readonly string $chainSymbol)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        resolve(CheckWalletService::class)
            ->checkUserDeposit(
                resolve(CheckUserDepositRequestDTO::class)
                    ->setUserId($this->userId)
                    ->setCurrencySymbol($this->currencySymbol)
                    ->setCurrencyChain($this->chainSymbol)
            );
    }
}
