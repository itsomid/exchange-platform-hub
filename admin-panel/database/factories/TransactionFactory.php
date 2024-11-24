<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
        ];
    }

    public function withReferralCodeUsage()
    {

        $user_id =  ReferralCode::query()
            ->inRandomOrder()
            ->value('user_id');

        return $this->state(function (array $attributes) use ($user_id) {
            return [
                'user_id' => $user_id,
                'type' => 'referral', // Creates a referral code and assigns it
                'status' => 'completed',
                'amount' => $this->faker->randomFloat(2, 2, 5),
                'description' => 'charge for referral',
            ];
        })->afterCreating(function (Transaction $transaction) use ($user_id) {
            $referralCode = ReferralCode::query()
                ->where('user_id', $user_id)
                ->first();
            $used_by = User::query()->where('introducer_code', $referralCode->id)->first();
            if ($referralCode) {
                ReferralCodeUsage::create([
                    'referral_code_id' => $referralCode->id,
                    'used_by' => $used_by->id,
                    'transaction_id' => $transaction->id,
                    'used_at' => now(),
                ]);
            }
        });
    }
}
