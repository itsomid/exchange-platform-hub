<?php

namespace App\Console\Commands;

use App\Services\Wallet\WithdrawalService;
use Illuminate\Console\Command;

class CheckWithdrawalWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:check-withdrawal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and processes pending withdrawals from the HD Wallet.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Checking pending withdrawals...');
        $checkWithdrawal = resolve(WithdrawalService::class);
        $checkWithdrawal->checkAllWithdrawal();
        $this->info('Withdrawal check completed.');
    }
}
