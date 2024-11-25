<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('currencies')->insert([
            [
                'type' => 'btc', // Bitcoin on its native blockchain
                'name' => 'Bitcoin',
                'symbol' => '₿',
                'code' => 'BTC',
                'logo' => 'btc.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'erc20', // Ethereum
                'name' => 'Ethereum',
                'symbol' => '⟠',
                'code' => 'ETH',
                'logo' => 'eth.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'trc20', // TRON token (TRC20)
                'name' => 'Tether',
                'symbol' => '₮',
                'code' => 'USDT',
                'logo' => 'usdt.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'erc20', // Ethereum token (ERC20)
                'name' => 'Tether',
                'symbol' => '₮ (ERC20)',
                'code' => 'USDT',
                'logo' => 'usdt.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'bep20', // Binance Smart Chain (BSC)
                'name' => 'Binance Coin',
                'symbol' => 'BNB',
                'code' => 'BNB',
                'logo' => 'bnb.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'dogecoin', // Dogecoin
                'name' => 'Dogecoin',
                'symbol' => 'DOGE',
                'code' => 'DOGE',
                'logo' => 'doge.svg',
                'is_active' => '1',
            ],
            [
                'type' => 'trc20', // TRON token (TRC20)
                'name' => 'Tron',
                'symbol' => 'TRX',
                'code' => 'TRX',
                'logo' => 'trx.svg', // Replace with actual logo path
                'is_active' => '1',
            ],
        ]);
    }

}
