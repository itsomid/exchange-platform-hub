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

        // Insert markets for each pair (without price and exchange_profit, as they are handled in ExchangePrice)
        $markets = [
            ['base_currency' => $btc->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.001, 'max_trade_amount' => 1000],
            ['base_currency' => $eth->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000],
            ['base_currency' => $doge->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 100000],
            ['base_currency' => $tron->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 10, 'max_trade_amount' => 1000000],
            ['base_currency' => $bnb->symbol, 'quote_currency' => $usdt->symbol, 'min_trade_amount' => 0.01, 'max_trade_amount' => 1000],
        ];

        foreach ($markets as $marketData) {
            // Insert the market into the markets table
            $market = Market::create([
                'base_currency' => $marketData['base_currency'],
                'quote_currency' => $marketData['quote_currency'],
                'min_trade_amount' => $marketData['min_trade_amount'],
                'max_trade_amount' => $marketData['max_trade_amount'],
                'is_active' => true,
            ]);

            // Insert price and exchange profit into the exchange_prices table for each market and exchange
            $exchangePrices = [
                ['market_id' => $market->id, 'exchange_id' => $binance->id, 'price' => 45000.00, 'exchange_profit_sell' => 0.01,'exchange_profit_buy'=>0.02],  // Binance price and profit
                ['market_id' => $market->id, 'exchange_id' => $coinex->id, 'price' => 45010.00, 'exchange_profit_sell' => 0.01,'exchange_profit_buy'=>0.02],  // CoinEx price and profit
            ];

            $insertData = Arr::random($exchangePrices);
            ExchangePrice::query()->create($insertData);

//            foreach ($exchangePrices as $exchangePriceData) {
//                ExchangePrice::create([
//                    'market_id' => $market->id,
//                    'exchange_id' => $exchangePriceData['exchange_id'],
//                    'price' => $exchangePriceData['price'],
//                    'exchange_profit' => $exchangePriceData['exchange_profit'],
//                ]);
//            }
        }
    }
}
