<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminTableSeeder extends Seeder
{
    const DEFAULT_SUPERVISOR = 1;

    public function run(): void
    {
        foreach ($this->admin() as $admin) {
            $admin = Admin::query()->create(array_merge($admin, [
                'password' => Hash::make('12345678'),
                'gender' => 'male',
            ]));

            $admin->assignRole('admin');
        }
        foreach ($this->auditor() as $admin) {
            $admin = Admin::query()->create(array_merge($admin, [
                'password' => Hash::make('12345678'),
                'gender' => 'female',
            ]));

            $admin->assignRole('auditor');
        }
        foreach ($this->support() as $admin) {
            $admin = Admin::query()->create(array_merge($admin, [
                'password' => Hash::make('12345678'),
                'gender' => 'female',
            ]));

            $admin->assignRole('support');
        }

    }

    private function admin(): array
    {
        return [
            [
                'id' => 1,
                'first_name' => 'اشکان',
                'last_name' => 'مهرگان',
                'email' => 'a.mehregan@gmail.com',
                'mobile' => '09124832327',
            ],
            [
                'id' => 2,
                'first_name' => 'امید',
                'last_name' => 'شبانی',
                'email' => 'o.shabani@hotmail.com',
                'mobile' => '09121990974',
            ],
            [
                'id' => 3,
                'first_name' => 'نریمان',
                'last_name' => 'پلنگی',
                'email' => 'n.palangi@gmail.com',
                'mobile' => '09125260985',
            ],
            [
                'id' => 4,
                'first_name' => 'مهدی',
                'last_name' => 'رجبی',
                'email' => 'mehdi@gmail.com',
                'mobile' => '09107588958',
            ],
            [
                'id' => 5,
                'first_name' => 'حسن',
                'last_name' => 'رضایی',
                'email' => 'h.rezaei@gmail.com',
                'mobile' => '09109529484',
            ],
            [
                'id' => 5,
                'first_name' => 'حسن',
                'last_name' => 'رضایی',
                'email' => 'a.arabgol@vista-group.ir',
                'mobile' => '09109529484',
            ],
        ];
    }

    private function auditor(): array
    {
        return [
            [
                'id' => 6,
                'first_name' => 'حسین',
                'last_name' => 'زکایی',
                'email' => 'h.zokaei@gmail.com',
                'mobile' => '09124063794',
            ],
        ];
    }

    private function support(): array
    {
        return [

            [
                'id' => 7,
                'first_name' => 'آرین',
                'last_name' => 'هاشمی',
                'email' => 'fazeli@gmail.com',
                'mobile' => '09913233751',
            ],
            [
                'id' => 8,
                'first_name' => 'گلناز',
                'last_name' => 'فرهمند',
                'email' => 'farahmand@gmail.com',
                'mobile' => '09399297035',
            ],
            [
                'id' => 9,
                'first_name' => 'سهراب',
                'last_name' => 'کلانتری',
                'email' => 'sohrab.kalantari@gmail.com',
                'mobile' => '09019887964',
            ],
        ];
    }
}
