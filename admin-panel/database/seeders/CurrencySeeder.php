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
                'logo' => 'btc.svg',  // Replace with actual logo file path or URL\
                'max_auto_withdraw_amount' => 0.25
            ],
            [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'logo' => 'eth.svg',  // Replace with actual logo file path or URL]
                'max_auto_withdraw_amount' => 2
            ],
            [
                'name' => 'Tether',
                'symbol' => 'USDT',
                'logo' => 'usdt.svg',  // Replace with actual logo file path or URL
                'precision' => 2,
                'max_auto_withdraw_amount' => 10000
            ],
            [
                'name' => 'TRON',
                'symbol' => 'TRX',
                'logo' => 'trx.svg',  // Replace with actual logo file path or URL
                'max_auto_withdraw_amount' => 100000
            ],
            [
                'name' => 'Binance Coin',
                'symbol' => 'BNB',
                'logo' => 'bnb.svg',  // Replace with actual logo file path or URL
                'max_auto_withdraw_amount' => 3
            ],
            [
                'name' => 'Dodge Coin',
                'symbol' => 'DOGE',
                'logo' => 'doge.svg',  // Replace with actual logo file path or URL
                'max_auto_withdraw_amount' =>  100000
            ],
            [
                'name' => 'CET Coin',
                'symbol' => 'CET',
                'logo' => 'cet.svg',  // Replace with actual logo file path or URL
                'max_auto_withdraw_amount' =>  1000000
            ],
        ]);
    }

}
