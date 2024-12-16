<?php

namespace Database\Seeders;

use App\Models\Exchange;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExchangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exchanges = [
            [
                'name' => 'CoinEx',
                'slug' => 'coinex',
                'priority' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Binance',
                'slug' => 'binance',
                'priority' => 2,
                'is_active' => false
            ],
            // Add more exchanges as needed
        ];

        // Insert data into exchanges table
        foreach ($exchanges as $exchange) {
            Exchange::create($exchange);
        }
    }

}
