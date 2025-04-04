<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpotTradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'maker_order_id' => 1, // Replace with real IDs in seeder
            'taker_order_id' => 2, // Replace with real IDs in seeder
            'market_id' => Market::query()->inRandomOrder()->first()->id,
            'quantity' => $this->faker->randomFloat(8, 0.01, 2),
            'price' => $this->faker->randomFloat(8, 100, 10000),
        ];
    }
}
