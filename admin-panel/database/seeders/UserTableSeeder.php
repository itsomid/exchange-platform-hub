<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'بیتکس روم',
            'last_name' => 'bitexroom',
            'username' => 'bitexroom',
            'email' => 'bitexroom@gmail.com',
            'password' => Hash::make('password'),
            'mobile' => '09121110111',
            'email_verified_at' => now()
        ]);

        User::create([
            'first_name' => 'امید',
            'last_name' => 'شبانی',
            'username' => 'o.shabani',
            'email' => 'o.shabani@hotmail.com',
            'password' => Hash::make('password'),
            'mobile' => '09121114113',
            'email_verified_at' => now()
        ]);
        User::create([
            'first_name' => 'محمد مهدی',
            'last_name' => 'رجبی',
            'username' => 'm.rajabi',
            'email' => 'mehdints@gmail.com',
            'password' => Hash::make('12345678'),
            'mobile' => '09121210112',
            'email_verified_at' => now()
        ]);
        User::create([
            'first_name' => 'نریمان',
            'last_name' => 'پلنگی',
            'username' => 'n.palangi',
            'email' => 'n.palangi91@gmail.com',
            'password' => Hash::make('12345678'),
            'mobile' => '09121110122',
            'email_verified_at' => now()
        ]);
        User::create([
            'first_name' => 'آریا',
            'last_name' => 'عرب گل',
            'username' => 'a.arabgol',
            'email' => 'a.arabgol@vista-group.ir',
            'password' => Hash::make('password'),
            'mobile' => '09121110113',
            'email_verified_at' => now()
        ]);

        User::factory(5)->withReferralCode()->create();
        User::factory(10)->withIntroducer()->create();
        //        User::factory(10)->create();
        //        User::factory(10)->unverifiedWithIncompleteRegistration()->create();
        //        User::factory(10)->unverified()->create();

    }
}
