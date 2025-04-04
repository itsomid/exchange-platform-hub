<?php

namespace Database\Factories;

use App\Models\SpotTrade;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradingCommissionFactory extends Factory
{
    public function definition(): array
    {
        $commission = $this->faker->randomFloat(8, 0.00001, 0.01);

        return [
            'spot_trade_id' => SpotTrade::factory(),
            'maker_commission_amount' => $commission,
            'maker_commission_percentage' => '0.001',
            'taker_commission_amount' => $commission,
            'taker_commission_percentage' => '0.001',
        ];
    }
}
