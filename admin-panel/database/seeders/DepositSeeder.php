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
        $users = User::where('id', '>', 1)->skip(0)->take(3)->get();

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

                    $amount = rand(1, 100) / 100; // Random deposit amount between 0.01 and 1.00
                    $transactionHash = 'txhash_' . bin2hex(random_bytes(10));

                    $depositService->confirmDeposit($deposit->id, $walletChain->wallet, $amount, $transactionHash);

                    echo "[CONFIRMED] User ID: {$user->id}, Currency: {$currency['symbol']}, Address: {$walletChain->address}, Amount: {$amount}, TxHash: {$transactionHash}\n";

                } catch (\Exception $e) {
                    echo "[ERROR] User ID: {$user->id}, Currency: {$currency['symbol']}: " . $e->getMessage() . "\n";
                }
            }
        }

    }
}
