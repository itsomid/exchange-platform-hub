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

        $currencies = [
            ['symbol' => 'BTC', 'chain' => 'BTC'],
            ['symbol' => 'ETH', 'chain' => 'ERC20'],
            ['symbol' => 'USDT', 'chain' => 'ERC20'],
            ['symbol' => 'BNB', 'chain' => 'BSC'],
            ['symbol' => 'TRX', 'chain' => 'TRC20'],
            ['symbol' => 'DOGE', 'chain' => 'DOGE'],
        ];

        $users = User::where('id', '!=', 1)->take(10)->get(); // Get 10 users from the database

        foreach ($users as $user) {
            foreach ($currencies as $currency) {
                try {
                    // Step 1: Create a deposit address for each currency
                    $walletChain = $walletService->createDepositAddress(
                        userId: $user->id,
                        currencySymbol: $currency['symbol'],
                        currencyChain: $currency['chain']
                    );

                    // Step 2: Find or create a pending deposit
                    $deposit = Deposit::where('address', $walletChain->address)
                        ->where('status', 'pending')
                        ->first();

                    if (!$deposit) {
                        throw new \Exception("No deposit found for the created wallet chain address.");
                    }

                    // Step 3: Simulate a deposit confirmation
                    $amount = rand(1, 100) / 100; // Random deposit amount between 0.01 and 1.00
                    $transactionHash = 'txhash_' . bin2hex(random_bytes(10)); // Example transaction hash

                    $depositService->confirmDeposit($deposit->id, $walletChain->wallet, $amount, $transactionHash);

                    echo "User ID: {$user->id}, Currency: {$currency['symbol']}, Deposit Address: {$walletChain->address}\n";
                    echo "Deposit ID: {$deposit->id} confirmed successfully.\n";
                } catch (\Exception $e) {
                    echo "Error for User ID: {$user->id}, Currency: {$currency['symbol']}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
