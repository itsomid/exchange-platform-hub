<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('currencies')->insert([
            [
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'logo' => 'btc.svg',  // Replace with actual logo file path or URL
            ],
            [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'logo' => 'eth.svg',  // Replace with actual logo file path or URL
            ],
            [
                'name' => 'Tether',
                'symbol' => 'USDT',
                'logo' => 'usdt.svg',  // Replace with actual logo file path or URL
            ],
            [
                'name' => 'TRON',
                'symbol' => 'TRX',
                'logo' => 'trx.svg',  // Replace with actual logo file path or URL
            ],
            [
                'name' => 'Binance Coin',
                'symbol' => 'BNB',
                'logo' => 'bnb.svg',  // Replace with actual logo file path or URL
            ],
            [
                'name' => 'Dodge Coin',
                'symbol' => 'DOGE',
                'logo' => 'doge.svg',  // Replace with actual logo file path or URL
            ],
        ]);
    }

}
