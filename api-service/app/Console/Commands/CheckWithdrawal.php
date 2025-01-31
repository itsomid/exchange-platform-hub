<?php

namespace App\Console\Commands;

use App\Services\Wallet\WithdrawalService;
use Illuminate\Console\Command;

class CheckWithdrawal extends Command
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = resolve(WithdrawalService::class);
        $service->checkWithdrawal();

    }
}
