<?php

namespace Database\Seeders;

use App\Enums\CurrencyChainEnum;
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
            ['symbol' => 'BTC', 'chain' => CurrencyChainEnum::BTC->value],
            ['symbol' => 'ETH', 'chain' => CurrencyChainEnum::ERC20->value],
            ['symbol' => 'USDT', 'chain' => CurrencyChainEnum::ERC20->value],
            ['symbol' => 'BNB', 'chain' => CurrencyChainEnum::BSC->value],
            ['symbol' => 'TRX', 'chain' => CurrencyChainEnum::TRC20->value],
            ['symbol' => 'DOGE', 'chain' => CurrencyChainEnum::DOGE->value],
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
                    $timestamp = now()->subDays(rand(0, 4))->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

                    // Step 2: Find or create a deposit record
                    $deposit = Deposit::firstOrCreate(
                        ['address' => $walletChain->address, 'status' => 'pending'],
                        [
                            'user_id' => $user->id,
                            'currency_symbol' => $currency['symbol'],
                            'currency_chain' => $currency['chain'],
                            'amount' => 0,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]
                    );

                    // Generate appropriate amount based on currency
                    $amount = match($currency['symbol']) {
                        'USDT', 'TRX', 'DOGE' => $this->generateAmount(100, 1000,2),
                        default => $this->generateAmount(0.01, 0.3) // For BTC, ETH, BNB
                    };

                    $transactionHash = 'txhash_' . bin2hex(random_bytes(10));

                    $depositService->confirmDeposit($deposit->id, $walletChain->wallet, $amount, $transactionHash);

                    echo "[CONFIRMED] User ID: {$user->id}, Currency: {$currency['symbol']}, Address: {$walletChain->address}, Amount: {$amount}, TxHash: {$transactionHash}\n";

                } catch (\Exception $e) {
                    echo "[ERROR] User ID: {$user->id}, Currency: {$currency['symbol']}: " . $e->getMessage() . "\n";
                }
            }
        }


    }

    private function generateAmount(float $min, float $max, int $decimals  = 8): float
    {
        $scale = pow(10, $decimals);
        $min = $min * $scale;
        $max = $max * $scale;
        return mt_rand($min, $max) / $scale;
    }
}
