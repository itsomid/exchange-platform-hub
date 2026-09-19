<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->unique()->word(),
            'persian_name' => $this->faker->unique()->word(),
            'symbol'       => strtoupper($this->faker->unique()->lexify('???')),
        ];
    }
}
