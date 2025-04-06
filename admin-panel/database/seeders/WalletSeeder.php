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
        // Only get currencies that have a currency chain relationship
        $currencies = Currency::has('chains')->pluck('symbol')->toArray();
        $user = User::find(1);
        foreach ($currencies as $currency) {
            \DB::table('wallets')->insert([
                'user_id' => $user->id,
                'currency_symbol' => $currency,
                'balance' => app()->environment('production') ? 0 : $this->generateAdminRealisticBalance($currency),
                'locked_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // Fetch all users from the users table
        $users = User::where('id', '!=', 1)->get();

        foreach ($users as $user) {

            foreach ($currencies as $currency) {
                \DB::table('wallets')->insert([
                    'user_id' => $user->id,
                    'currency_symbol' => $currency,
                    'balance' => app()->environment('production') ? 0 : $this->generateRealisticBalance($currency),
                    'locked_balance' => app()->environment('production') ? 0 : $this->generateRealisticLockedBalance($currency),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    private function generateAdminRealisticBalance(string $currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return $this->randomFloat(8, 10, 8);
            case 'ETH':
                return $this->randomFloat(90, 100, 8);
            case 'DOGE':
                return $this->randomFloat(90000, 100000, 2);
            case 'BNB':
                return $this->randomFloat(180, 200, 8);
            case 'USDT':
                return $this->randomFloat(80000, 100000, 2);
            case 'TRX':
                return $this->randomFloat(80000, 100000, 2);
            default:
                return $this->randomFloat(0, 1000, 8); // Default range for other currencies

        }
    }
    private function generateRealisticBalance(string $currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return $this->randomFloat(1, 3, 8);
            case 'ETH':
                return $this->randomFloat(10, 20, 8);
            case 'DOGE':
                return $this->randomFloat(20000, 100000, 2);
            case 'BNB':
                return $this->randomFloat(15, 100, 8);
            case 'USDT':
                return $this->randomFloat(2000, 10000, 2);
            case 'TRX':
                return $this->randomFloat(30000, 100000, 2);
            default:
                return $this->randomFloat(0, 1000, 8); // Default range for other currencies
        }
    }
    private function generateRealisticLockedBalance(string $currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return $this->randomFloat(0, 1, 8);
            case 'ETH':
                return $this->randomFloat(0, 10, 8);
            case 'DOGE':
                return $this->randomFloat(0, 20000, 2);
            case 'BNB':
                return $this->randomFloat(0, 15, 8);
            case 'USDT':
                return $this->randomFloat(0, 2000, 2);
            case 'TRX':
                return $this->randomFloat(0, 30000, 2);
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
