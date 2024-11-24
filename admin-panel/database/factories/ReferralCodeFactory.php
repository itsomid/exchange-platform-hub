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
        $friend_fee = $this->faker->randomFloat(0, 0, 30);
        return [
            'user_id' => User::factory(), // Generate a user who is the introducer
            'code' => ReferralCode::generateReferralCode(), // Generate a unique referral code
            'friend_fee' => $friend_fee, // Random friend fee
            'introducer_fee' => 30 - $friend_fee, // Random introducer fee
            'usage_limit' => $this->faker->optional()->numberBetween(1, 100), // Optional usage limit
        ];
    }
}
