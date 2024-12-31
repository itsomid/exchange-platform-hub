<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Deposit\DepositService;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws \Exception
     */
    public function run(): void
    {
        $walletService = new WalletService();
        $depositService = new DepositService();

        $user = User::find(2);

        $currencySymbol = 'USDT';
        $currencyChain = 'BEP20';

        $walletChain = $walletService->createDepositAddress(
            userId: $user->id,
            currencySymbol: $currencySymbol,
            currencyChain: $currencyChain
        );

        // Step 3: Simulate a deposit confirmation
        $depositId = $walletChain->wallet->deposits->first()->id; // Assuming a deposit is created for this wallet
        $amount = 0.01; // Example deposit amount
        $transactionHash = 'txhash_' . bin2hex(random_bytes(10)); // Example transaction hash

        $depositService->confirmDeposit($depositId, $amount, $transactionHash);


        echo "Deposit Address: {$walletChain->address}\n";
        echo "Deposit ID: {$depositId} confirmed successfully.\n";
    }
}
