<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->items as $item) {
            Setting::query()->create($item);
        }
    }

    private array $items = [
        ['key' => 'ref_base_address', 'value' => 'http://127.0.0.1:8002/api'],
        ['key' => 'service_address', 'value' => 'http://localhost:3005/v1/api/admin/'],
    ];
}
