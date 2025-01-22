<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;


use App\Services\Withdrawal\WithdrawalService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    private WithdrawalService $withdrawalService;

    private array $currencies = [
        ['symbol' => 'BTC', 'chain' => 'BTC', 'address' => 'bc1qsl4egjdl8s3mw822mmakvzsup7sedkd4n5755d'],
        ['symbol' => 'ETH', 'chain' => 'ETH', 'address' => '0x1234567890abcdef1234567890abcdef12345678'],
        ['symbol' => 'TRX', 'chain' => 'TRX', 'address' => 'T1234567890abcdef1234567890abcdef12345678'],
        ['symbol' => 'DOGE', 'chain' => 'DOGE', 'address' => 'D1234567890abcdef1234567890abcdef12345678'],
        ['symbol' => 'BNB', 'chain' => 'BSC', 'address' => 'bnb1qsl4egjdl8s3mw822mmakvzsup7sedkd4n5755d'],
        ['symbol' => 'USDT', 'chain' => 'ERC20', 'address' => '0xabcdefabcdefabcdefabcdefabcdefabcdef'],
    ];
    public function __construct()
    {
        $this->withdrawalService = app(WithdrawalService::class);
    }
    public function run(): void
    {
        $startDate = now()->subDays(5);

        foreach ($this->currencies as $currency) {
            for ($i = 1; $i < 6; $i++) {
                $date = $startDate->copy()->addDays($i);

                $this->processWithdrawals($this->generateRealisticBalance($currency['symbol']), 0, 3, false, $currency, $date);
                $this->processWithdrawals($this->generateRealisticBalance($currency['symbol']), 3, 3, true, $currency, $date);
//                $this->processWithdrawals(0.2, 6, 3, false, $currency, $date);
            }
        }
    }

    private function processWithdrawals(float $totalAmount, int $skip, int $limit, bool $confirm, array $currency, Carbon $date): void
    {
        $users = User::where('id', '>', 1)->skip($skip)->take($limit)->get();

        foreach ($users as $user) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency_symbol' => $currency['symbol']],
                ['balance' => 1.0, 'locked_balance' => 0]
            );

            try {
                $withdrawal = $this->withdrawalService->createWithdrawal(
                    userId: $user->id,
                    walletId: $wallet->id,
                    currencyChain: $currency['chain'],
                    currencySymbol: $currency['symbol'],
                    totalAmount: $totalAmount,
                    address: $currency['address'],
                    description: 'Test withdrawal for seeding',
                    date: $date,
                );

                if ($confirm) {
                    $transactionHash = '44dbec29398e9844694d317928281ff36b873d93c07ab18bcfbb7b098a91523a' . rand(1, 9999);
                    $this->withdrawalService->confirmWithdrawal($withdrawal->id, $wallet->id, $transactionHash);
                    echo "Withdrawal created and confirmed for User ID: {$user->id}, Currency: {$currency['symbol']}, Hash: {$transactionHash}\n";
                } else {
                    echo "Withdrawal created for User ID: {$user->id}, Currency: {$currency['symbol']}\n";
                }
            } catch (\Exception $e) {
                echo "Error processing withdrawal for User ID {$user->id}, Currency: {$currency['symbol']}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function generateRealisticBalance(string $currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return $this->randomFloat(0.001, 0.5, 8);
            case 'ETH':
                return $this->randomFloat(0.001, 1, 8);
            case 'DOGE':
                return $this->randomFloat(10, 1000, 2);
            case 'BNB':
                return $this->randomFloat(0.5, 1, 8);
            case 'USDT':
                return $this->randomFloat(50, 10000, 2);
            case 'TRX':
                return $this->randomFloat(10, 1000, 2);
            default:
                return $this->randomFloat(0, 1000, 8); // Default range for other currencies
        }
    }

    private function randomFloat(float $min, float $max, int $decimals): float
    {
        $scale = pow(10, $decimals);
        return mt_rand($min * $scale, $max * $scale) / $scale;
    }
}
