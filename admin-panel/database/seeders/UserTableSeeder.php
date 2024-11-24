<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)->withReferralCode()->create();
        //        User::factory(10)->create();
        //        User::factory(10)->unverifiedWithIncompleteRegistration()->create();
        //        User::factory(10)->unverified()->create();

    }
}
