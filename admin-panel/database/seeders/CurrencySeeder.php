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
        $currencies = [
            [
                'name' => 'Bitcoin',
                'persian_name' => 'بیت کوین',
                'symbol' => 'BTC',
                'logo' => 'btc.svg',
                'price_precision' => 2,
                'amount_precision' => 8,
                'max_auto_withdraw_amount' => 0.25,
            ],
            [
                'name' => 'Ethereum',
                'persian_name' => 'اتریوم',
                'symbol' => 'ETH',
                'logo' => 'eth.svg',
                'price_precision' => 2,
                'amount_precision' => 6,
                'max_auto_withdraw_amount' => 2,
            ],
            [
                'name' => 'Tether',
                'persian_name' => 'تتر',
                'symbol' => 'USDT',
                'logo' => 'usdt.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 10000,
            ],
            [
                'name' => 'TRON',
                'persian_name' => 'ترون',
                'symbol' => 'TRX',
                'logo' => 'trx.svg',
                'price_precision' => 5,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'Binance Coin',
                'persian_name' => 'بایننس کوین',
                'symbol' => 'BNB',
                'logo' => 'bnb.svg',
                'price_precision' => 2,
                'amount_precision' => 5,
                'max_auto_withdraw_amount' => 3,
            ],
            [
                'name' => 'Doge Coin',
                'persian_name' => 'دوج کوین',
                'symbol' => 'DOGE',
                'logo' => 'doge.svg',
                'price_precision' => 5,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'CET Coin',
                'persian_name' => 'ست کوین',
                'symbol' => 'CET',
                'logo' => 'cet.svg',
                'price_precision' => 6,
                'amount_precision' => 0,
                'max_auto_withdraw_amount' => 1000000,
            ],
            [
                'name' => 'Audios',
                'persian_name' => 'آدیوس',
                'symbol' => 'AUDIO',
                'logo' => 'audio.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 50000,
            ],
            [
                'name' => 'Kyber Network Crystal v2',
                'persian_name' => 'کیبر نتورک',
                'symbol' => 'KNC',
                'logo' => 'knc.svg',
                'price_precision' => 3,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 50000,
            ],
            [
                'name' => 'Dai',
                'persian_name' => 'دای',
                'symbol' => 'DAI',
                'logo' => 'dai.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'Alien Worlds',
                'persian_name' => 'ایلین ورلدز',
                'symbol' => 'TLM',
                'logo' => 'tlm.svg',
                'price_precision' => 5,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'Pundi X',
                'persian_name' => 'پاندی ایکس',
                'symbol' => 'PUNDIX',
                'logo' => 'pundix.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 50000,
            ],
            [
                'name' => 'Axie Infinity',
                'persian_name' => 'اکسی اینفینیتی',
                'symbol' => 'AXS',
                'logo' => 'axs.svg',
                'price_precision' => 2,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 10000,
            ],
            [
                'name' => 'The Sandbox',
                'persian_name' => 'سندباکس',
                'symbol' => 'SAND',
                'logo' => 'sand.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'Gala',
                'persian_name' => 'گالا',
                'symbol' => 'GALA',
                'logo' => 'gala.svg',
                'price_precision' => 5,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
            [
                'name' => 'Uniswap',
                'persian_name' => 'یونی‌سواپ',
                'symbol' => 'UNI',
                'logo' => 'uni.svg',
                'price_precision' => 2,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 10000,
            ],
            [
                'name' => 'xMoney',
                'persian_name' => 'اکس مانی',
                'symbol' => 'UTK',
                'logo' => 'utk.svg',
                'price_precision' => 4,
                'amount_precision' => 2,
                'max_auto_withdraw_amount' => 100000,
            ],
        ];

        \DB::table('currencies')->insert($currencies);
    }
}
