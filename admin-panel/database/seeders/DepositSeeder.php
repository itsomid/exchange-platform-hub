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
            ['symbol' => 'ETH', 'chain' => 'ETH'],
            ['symbol' => 'USDT', 'chain' => 'ERC20'],
            ['symbol' => 'BNB', 'chain' => 'BSC'],
            ['symbol' => 'TRX', 'chain' => 'TRX'],
            ['symbol' => 'DOGE', 'chain' => 'DOGE'],
        ];

        User::where('id', '!=', 1)->chunk(5, function ($users) use ($walletService, $depositService, $currencies) {
            foreach ($users as $user) {
                foreach ($currencies as $currency) {
                    try {
                        // Step 1: Create a deposit address for the user
                        $walletChain = $walletService->createDepositAddress(
                            userId: $user->id,
                            currencySymbol: $currency['symbol'],
                            currencyChain: $currency['chain']
                        );

                        // Step 2: Find or create a deposit record
                        $deposit = Deposit::firstOrCreate(
                            ['address' => $walletChain->address, 'status' => 'pending'],
                            ['user_id' => $user->id, 'currency' => $currency['symbol'], 'amount' => 0]
                        );

                        // Step 3: Simulate deposit confirmation or leave unconfirmed
                        $isConfirmed = rand(0, 1); // Randomly decide whether to confirm the deposit

                        if ($isConfirmed) {
                            $amount = rand(1, 100) / 100; // Random deposit amount between 0.01 and 1.00
                            $transactionHash = 'txhash_' . bin2hex(random_bytes(10));

                            $depositService->confirmDeposit($deposit->id, $walletChain->wallet, $amount, $transactionHash);

                            echo "[CONFIRMED] User ID: {$user->id}, Currency: {$currency['symbol']}, Address: {$walletChain->address}, Amount: {$amount}, TxHash: {$transactionHash}\n";
                        } else {
                            echo "[PENDING] User ID: {$user->id}, Currency: {$currency['symbol']}, Address: {$walletChain->address}, Deposit ID: {$deposit->id} remains unconfirmed.\n";
                        }
                    } catch (\Exception $e) {
                        echo "[ERROR] User ID: {$user->id}, Currency: {$currency['symbol']}: " . $e->getMessage() . "\n";
                    }
                }
            }
        });
    }
}
