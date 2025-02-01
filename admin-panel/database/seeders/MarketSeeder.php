<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

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

        // Get exchanges (assuming exchanges already exist)
        $binance = Exchange::where('slug', 'binance')->first();
        $coinex = Exchange::where('slug', 'coinex')->first();

        // Define actual prices for each base currency (in USDT)
        $priceMap = [
            'BTC' => 104300.00,    // Bitcoin price
            'ETH' => 3266.24,     // Ethereum price
            'DOGE' => 0.3278,       // Dogecoin price
            'TRX' => 0.2547,        // Tron price
            'BNB' => 678.22,      // Binance Coin price
        ];

        // Insert markets for each pair (without price and exchange_profit, as they are handled in ExchangePrice)
        $markets = [
            ['base_currency' => $btc->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.001, 'max_trade_amount' => 1000,  'min_otc_amount' => 0.00005000, 'max_otc_amount' => 1000],
            ['base_currency' => $eth->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000, 'min_otc_amount' => 0.00050000, 'max_otc_amount' => 1000],
            ['base_currency' => $doge->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 100000, 'min_otc_amount' => 5, 'max_otc_amount' => 1000],
            ['base_currency' => $tron->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 1000000, 'min_otc_amount' => 5, 'max_otc_amount' => 1000],
            ['base_currency' => $bnb->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000, 'min_otc_amount' => 0.00500000, 'max_otc_amount' => 1000],
        ];

        foreach ($markets as $marketData) {
            // Insert the market into the markets table
            $market = Market::create([
                'base_currency' => $marketData['base_currency'],
                'quote_currency' => $marketData['quote_currency'],
                'min_trade_amount' => $marketData['min_trade_amount'],
                'max_trade_amount' => $marketData['max_trade_amount'],
                'min_otc_amount' => $marketData['min_otc_amount'],
                'max_otc_amount' => $marketData['max_otc_amount'],
                'is_active' => true,
            ]);


            $currentPrice = $priceMap[$marketData['base_currency']];

            // Create exchange price entry with actual price
            ExchangePrice::create([
                'market_id' => $market->id,
                'exchange_id' => $coinex->id,
                'price' => $currentPrice,
                'exchange_profit_sell' => 3,
                'exchange_profit_buy' => -2
            ]);
        }
    }
}
