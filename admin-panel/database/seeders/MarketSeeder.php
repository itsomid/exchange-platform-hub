<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Market;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the required currencies exist
        $btc = Currency::where('symbol', 'BTC')->first();
        $usdt = Currency::where('symbol', 'USDT')->first();
        $eth = Currency::where('symbol', 'ETH')->first();
        $doge = Currency::where('symbol', 'DOGE')->first();
        $tron = Currency::where('symbol', 'TRX')->first();
        $bnb = Currency::where('symbol', 'BNB')->first();

        // Insert markets for each pair
        $markets = [
            ['base_currency' => $btc->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.001, 'max_trade_amount' => 1000, 'price' => 45000.00, 'exchange_price' => 45000.00],
            ['base_currency' => $eth->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000, 'price' => 3000.00, 'exchange_price' => 3000.00],
            ['base_currency' => $doge->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 100000, 'price' => 0.25, 'exchange_price' => 0.25],
            ['base_currency' => $tron->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 1000000, 'price' => 0.08, 'exchange_price' => 0.08],
            ['base_currency' => $bnb->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000, 'price' => 400.00, 'exchange_price' => 400.00],
        ];

        foreach ($markets as $market) {
            Market::create([
                'base_currency' => $market['base_currency'],
                'quote_currency' => $market['quote_currency'],
                'min_trade_amount' => $market['min_trade_amount'],
                'max_trade_amount' => $market['max_trade_amount'],
                'price' => $market['price'],
                'exchange_price' => $market['exchange_price'],
                'is_active' => true,
            ]);
        }
    }
}
