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
                    'balance' => $this->generateRandomBalance(),
                    'locked_balance' => $this->generateRandomBalance(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    private function generateRandomBalance()
    {
        // Generate a random balance between 0 and 1000 with 8 decimal places
        return rand(0, 1000) + rand(0, 99999999) / 100000000;
    }
}
