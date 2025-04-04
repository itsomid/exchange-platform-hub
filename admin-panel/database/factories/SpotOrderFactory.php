<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpotOrderFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['limit', 'market']);
        return [
            'user_id' => User::factory(),
            'market_id' => Market::query()->inRandomOrder()->first()->id,
            'side' => $this->faker->randomElement(['buy', 'sell']),
            'type' => $type,
            'quantity' => $this->faker->randomFloat(8, 0.01, 5),
            'price' => $type === 'limit' ? $this->faker->randomFloat(8, 100, 10000) : null,
            'status' => 'open',
            'filled_quantity' => 0,
        ];
    }
}
