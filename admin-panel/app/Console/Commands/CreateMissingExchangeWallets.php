<?php

namespace App\Console\Commands;

use App\Services\Wallet\WalletService;
use Illuminate\Console\Command;

class CreateMissingExchangeWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:create-missing-exchange-wallets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create exchange wallets for all currencies that do not have them';

    /**
     * Execute the console command.
     */
    public function handle(WalletService $walletService): int
    {
        $this->info('Checking for missing exchange wallets...');

        try {
            $createdWallets = $walletService->createMissingExchangeWallets();

            if (empty($createdWallets)) {
                $this->info('All currencies already have exchange wallets.');
                return 0;
            }

            $this->info('Successfully created exchange wallets for the following currencies:');

            foreach ($createdWallets as $wallet) {
                $this->line("- {$wallet->currency_symbol}");
            }

            $this->info("Total wallets created: " . count($createdWallets));

            return 0;
        } catch (\Throwable $exception) {
            $this->error('Error creating exchange wallets: ' . $exception->getMessage());
            report($exception);
            return 1;
        }
    }
}
