<?php

namespace Database\Seeders;

use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Models\TradingCommission;
use Illuminate\Database\Seeder;

class SpotOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Create 20 spot orders
        SpotOrder::factory()->count(20)->create();

        // You could also create a sample trade manually to match valid maker/taker
        $maker = SpotOrder::factory()->state([
            'type' => 'limit',
            'price' => 15000
        ])->create();
        $taker = SpotOrder::factory()->state([
            'type' => 'market',
            'price' => null,
            'market_id' => $maker->market_id
        ])->create();

        $trade = SpotTrade::factory()->state([
            'maker_order_id' => $maker->id,
            'taker_order_id' => $taker->id,
            'market_id' => $maker->market_id,
        ])->create();

        TradingCommission::factory()->create([
            'spot_trade_id' => $trade->id,
        ]);
    }
}
