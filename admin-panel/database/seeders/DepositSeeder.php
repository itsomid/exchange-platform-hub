<?php

namespace Database\Seeders;

use App\Models\Deposit;
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

        $currencySymbol = 'BTC';
        $currencyChain = 'BTC';

        $walletChain = $walletService->createDepositAddress(
            userId: $user->id,
            currencySymbol: $currencySymbol,
            currencyChain: $currencyChain
        );

        $deposit = Deposit::where('address', $walletChain->address)->where('status','pending')->first();

        if (!$deposit) {
            throw new \Exception("No deposit found for the created wallet chain address.");
        }
        // Step 3: Simulate a deposit confirmation

        $amount = 0.01; // Example deposit amount
        $transactionHash = 'txhash_' . bin2hex(random_bytes(10)); // Example transaction hash

        $depositService->confirmDeposit($deposit->id,$walletChain->wallet, $amount, $transactionHash);


        echo "Deposit Address: {$walletChain->address}\n";
        echo "Deposit ID: {$deposit->id} confirmed successfully.\n";
    }
}
