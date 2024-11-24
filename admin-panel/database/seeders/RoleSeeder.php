<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $role) {
            Role::create([
                'name' => $role[0],
                'persian_name' => $role[1],
                'guard_name' => 'admin',
            ]);
        }

    }

    private function data()
    {
        return [
            ['super_admin', 'مدیر ارشد'],
            ['admin', 'مدیر'],
            ['support', 'پشتیبان'],
            ['auditor', 'حسابرس'],
            ['sales_manager', 'مدیر فروش'],
            ['tech_support', 'پشتیبانی فنی (Tech)'],
            ['tech_developers', 'توسعه دهنده(Tech Developer)'],
        ];
    }
}
