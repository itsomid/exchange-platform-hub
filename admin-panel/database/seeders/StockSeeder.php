<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        Stock::insert([
            [
                'name' => 'سهام عادی معجزه قرن',
                'value' => 20,
                'type' => 'normal',
                'cancellation_fee' => 2,
                'description' => 'سهام عادی پروژه معجزه قرن',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'سهام هدیه معجزه قرن',
                'value' => 5,
                'type' => 'gift',
                'cancellation_fee' => 2,
                'description' => 'سهام هدیه برای کاربران خاص',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'سهام همکار معجزه قرن',
                'value' => 12,
                'type' => 'partner',
                'cancellation_fee' => 2,
                'description' => 'سهام ویژه همکاران پروژه',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
