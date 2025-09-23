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
                'persian_name' => 'بیت کوین',
                'symbol' => 'BTC',
                'logo' => 'btc.svg',  // Replace with actual logo file path or URL\
                'price_precision' => 2,
                'amount_precision' => 8,
                'max_auto_withdraw_amount' => 0.25
            ],
            [
                'name' => 'Ethereum',
                'persian_name' => 'اتریوم',
                'symbol' => 'ETH',
                'logo' => 'eth.svg',  // Replace with actual logo file path or URL]
                'price_precision' => 2,
                'amount_precision' => 6,
                'max_auto_withdraw_amount' => 2
            ],
            [
                'name' => 'Tether',
                'persian_name' => 'تتر',
                'symbol' => 'USDT',
                'logo' => 'usdt.svg',  // Replace with actual logo file path or URL
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 10000
            ],
            [
                'name' => 'TRON',
                'persian_name' => 'ترون',
                'symbol' => 'TRX',
                'logo' => 'trx.svg',  // Replace with actual logo file path or URL
                'price_precision' => 6,
                'amount_precision' => 0,
                'max_auto_withdraw_amount' => 100000
            ],
            [
                'name' => 'Binance Coin',
                'persian_name' => 'بایننس کوین',
                'symbol' => 'BNB',
                'logo' => 'bnb.svg',  // Replace with actual logo file path or URL
                'price_precision' => 2,
                'amount_precision' => 5,
                'max_auto_withdraw_amount' => 3
            ],
            [
                'name' => 'Dodge Coin',
                'persian_name' => 'دوج کوین',
                'symbol' => 'DOGE',
                'logo' => 'doge.svg',  // Replace with actual logo file path or URL
                'price_precision' => 6,
                'amount_precision' => 0,
                'max_auto_withdraw_amount' =>  100000
            ],
            [
                'name' => 'CET Coin',
                'persian_name' => 'ست کوین',
                'symbol' => 'CET',
                'logo' => 'cet.svg',  // Replace with actual logo file path or URL
                'price_precision' => 6,
                'amount_precision' => 0,
                'max_auto_withdraw_amount' =>  1000000
            ],
        ]);
    }

}
