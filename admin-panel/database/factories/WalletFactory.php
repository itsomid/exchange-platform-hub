<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency_symbol' =>  Market::query()->inRandomOrder()->first()->base_currency,
            'balance' => $balance = $this->faker->randomFloat(0, 2),
            'locked_balance' => $this->faker->randomFloat(0, $balance),
        ];
    }
}
