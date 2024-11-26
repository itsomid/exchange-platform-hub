<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        //        $this->seedAdminsWithReferralCodes();
        $this->seedAdditionalData();
    }

    private function seedRolesAndPermissions(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    private function seedAdditionalData(): void
    {
        $this->call([
            AdminTableSeeder::class,
            ReferralCodeSeeder::class,
            UserTableSeeder::class,
            CurrenciesSeeder::class,
            TransactionSeeder::class,
            SettingTableSeeder::class,
        ]);
    }
}
