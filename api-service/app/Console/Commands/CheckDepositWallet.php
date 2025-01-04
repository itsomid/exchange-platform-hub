<?php

namespace App\Console\Commands;

use App\Services\Wallet\CheckWalletService;
use Illuminate\Console\Command;

class CheckDepositWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:check-deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check deposit wallet for new transactions';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $walletService = resolve(CheckWalletService::class);
        $walletService->checkDepositWallet();
    }
}
