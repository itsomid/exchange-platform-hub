<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpotOrderFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['limit', 'market']);
        $market = Market::query()->inRandomOrder()->first();
        $user = User::factory()->create();
        Wallet::query()->firstOrCreate([
            'user_id' => $user->id,
            'currency_symbol' => $market->base_currency,
        ], [
            'balance' => $this->faker->randomFloat(8, 0, 100),
            'locked_balance' => 0
        ]);

        Wallet::query()->firstOrCreate([
            'user_id' => $user->id,
            'currency_symbol' => 'USDT',
        ], [
            'balance' => $this->faker->randomFloat(8, 0, 100),
            'locked_balance' => 0
        ]);

        return [
            'user_id' => $user->id,
            'market_id' => $market->id,
            'side' => $this->faker->randomElement(['buy', 'sell']),
            'type' => $type,
            'quantity' => $this->faker->randomFloat(8, 0.01, 5),
            'price' => $type === 'limit' ? $this->faker->randomFloat(8, 100, 10000) : null,
            'status' => 'open',
            'filled_quantity' => 0,
        ];
    }
}
