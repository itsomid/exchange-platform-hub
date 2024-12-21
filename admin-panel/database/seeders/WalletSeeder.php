<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = Currency::pluck('symbol')->toArray();

        // Fetch all users from the users table
        $users = User::all();

        foreach ($users as $user) {
            foreach ($currencies as $currency) {
                \DB::table('wallets')->insert([
                    'user_id' => $user->id,
                    'currency_symbol' => $currency,
                    'balance' => $this->generateRealisticBalance($currency),
                    'locked_balance' => $this->generateRealisticBalance($currency),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function generateRealisticBalance(string $currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return $this->randomFloat(0, 10, 8);
            case 'ETH':
                return $this->randomFloat(0, 100, 8);
            case 'DOGE':
                return $this->randomFloat(0, 100000, 2);
            case 'BNB':
                return $this->randomFloat(0, 100, 8);
            case 'USDT':
                return $this->randomFloat(0, 10000, 2);
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
