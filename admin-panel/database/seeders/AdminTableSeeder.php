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
        foreach ($this->superAdmin() as $admin) {
            $admin = Admin::query()->create(array_merge($admin, [
                'password' => Hash::make('12345678'),
                'gender' => 'male',
            ]));

            $admin->assignRole('super-admin');
        }
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
    private function superAdmin(): array
    {
        return [
            [
                'id' => 1,
                'first_name' => 'علی اکبر',
                'last_name' => 'توسل',
                'email' => 'admin@bitexroom.com',
                'mobile' => '091212312312',
            ],
            [
                'id' => 2,
                'first_name' => 'امید',
                'last_name' => 'شبانی',
                'email' => 'o.shabani@hotmail.com',
                'mobile' => '09121990974',
            ],

        ];
    }
    private function admin(): array
    {


        return [
            [
                'id' => 3,
                'first_name' => 'نریمان',
                'last_name' => 'پلنگی',
                'email' => 'n.palangi@gmail.com',
                'mobile' => '09125260985',
            ],
            [
                'id' => 4,
                'first_name' => 'اشکان',
                'last_name' => 'مهرگان',
                'email' => 'a.mehregan@gmail.com',
                'mobile' => '09124832327',
            ],
            [
                'id' => 5,
                'first_name' => 'مهدی',
                'last_name' => 'رجبی',
                'email' => 'mehdi@gmail.com',
                'mobile' => '09107588958',
            ],
            [
                'id' => 6,
                'first_name' => 'حسین',
                'last_name' => 'زکایی',
                'email' => 'h.zokaei@gmail.com',
                'mobile' => '09124063794',
            ],
            [
                'id' => 7,
                'first_name' => 'آریا',
                'last_name' => 'عرب گل',
                'email' => 'a.arabgol@vista-group.ir',
                'mobile' => '09109529485',
            ]
        ];
    }

    private function auditor(): array
    {
        return [
            [
                'id' => 8,
                'first_name' => 'فروزان',
                'last_name' => 'عرب نیا',
                'email' => 'f.arabnia@gmail.com',
                'mobile' => '09124063795',
            ],
        ];
    }

    private function support(): array
    {
        return [

            [
                'id' => 9,
                'first_name' => 'گلناز',
                'last_name' => 'فرهمند',
                'email' => 'farahmand@gmail.com',
                'mobile' => '09399297035',
            ],
            [
                'id' => 10,
                'first_name' => 'سهراب',
                'last_name' => 'کلانتری',
                'email' => 'sohrab.kalantari@gmail.com',
                'mobile' => '09019887964',
            ],
        ];
    }
}
