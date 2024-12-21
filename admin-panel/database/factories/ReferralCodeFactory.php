<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $introducer_fee = $this->faker->randomElement([5, 10, 15, 20, 25, 30]);
        return [
            'user_id' => User::factory(), // Generate a user who is the introducer
            'code' => ReferralCode::generateReferralCode(), // Generate a unique referral code
            'introducer_fee' => $introducer_fee, // Random introducer fee
            'friend_fee' => 30 - $introducer_fee, // Random friend fee
            'usage_limit' => $this->faker->optional()->numberBetween(1, 100), // Optional usage limit
        ];
    }
}
