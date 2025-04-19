<?php

namespace Database\Seeders;

use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Models\TradingCommission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SpotOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Create 20 spot orders
        SpotOrder::factory()->count(20)->create();

        $amountOrderOne = 1;
        $amountOrderTwo = Arr::random([.5, 1]);
        // You could also create a sample trade manually to match valid maker/taker
        $maker = SpotOrder::factory()->state([
            'type' => 'limit',
            'price' => 15000,
            'side' => 'buy',
            'quantity' => $amountOrderOne,
            'filled_quantity' => $amountOrderTwo,
            'status' => $amountOrderTwo === 1 ? 'completed' : 'open',
        ])->create();
        $taker = SpotOrder::factory()->state([
            'type' => 'market',
            'price' => null,
            'market_id' => $maker->market_id,
            'side' => 'sell',
            'quantity' => $amountOrderTwo,
            'filled_quantity' => $amountOrderTwo,
            'status' => 'completed',
        ])->create();

        $trade = SpotTrade::factory()->state([
            'maker_order_id' => $maker->id,
            'taker_order_id' => $taker->id,
            'market_id' => $maker->market_id,
            'quantity' => $amountOrderTwo,
        ])->create();

        TradingCommission::factory()->state([
            'spot_trade_id' => $trade->id,
            'maker_commission_amount' => $amountOrderTwo * 0.001,
            'taker_commission_amount' => $amountOrderTwo * 0.001
        ])->create();
    }
}
