<?php

namespace Database\Seeders;

use App\Models\ReferralCode;
use Illuminate\Database\Seeder;

class ReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReferralCode::factory(10)->create();
    }

    public function withReferralCode()
    {
        return $this->state(function (array $attributes) {
            return [
                'introducer_code' => ReferralCode::inRandomOrder()->value('id'), // Creates a referral code and assigns it
            ];
        });
    }
}
