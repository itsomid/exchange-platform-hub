<?php

namespace Database\Seeders;

use App\Models\ExternalApiConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExternalApiConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExternalApiConfig::create([
            'name' => 'CoinMarketCap',
            'key' => '98490aeb-9951-4d07-9ea2-52c38f0bb771',
            'status' => 'active',
        ],
        [
            'name' => 'Cryptocompare',
            'key' => '58a4ce00281069f74475bf860babe5a7bdcfe28c2e27fbf014bc6e8efbda8f37',
            'status' => 'active',
        ]);
    }
}
