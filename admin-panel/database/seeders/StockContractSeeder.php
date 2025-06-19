<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockContract;
use App\Models\User;
use App\Models\Stock;
use Illuminate\Support\Str;

class StockContractSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $stock = Stock::first();
        if ($user && $stock) {
            StockContract::create([
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'contract_number' => StockContract::generateContractNumber(),
                'contract_file' => null,
                'amount' => 10,
                'total_value' => 100,
                'contract_status' => 'active',
                'cancellation_fee' => 20,
                'cancelled_at' => null,
                'sold_at' => null,
                'description' => 'قرارداد نمونه برای تست',
            ]);
        }
    }
}
