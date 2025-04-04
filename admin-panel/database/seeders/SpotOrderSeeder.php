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
        $maker = SpotOrder::factory()->create(['type' => 'limit', 'price' => 15000]);
        $taker = SpotOrder::factory()->create(['type' => 'market', 'price' => null]);

        $trade = SpotTrade::factory()->create([
            'maker_order_id' => $maker->id,
            'taker_order_id' => $taker->id,
            'market_id' => $maker->market_id,
        ]);

        TradingCommission::factory()->create([
            'spot_trade_id' => $trade->id,
        ]);
    }
}
