<?php

namespace Database\Seeders;

use App\Models\Bot\BotGlobalSettings;
use Illuminate\Database\Seeder;

class BotGlobalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (BotGlobalSettings::exists()) {
            return;
        }

        BotGlobalSettings::create([
            'min_deposit_usdt'          => 20,
            'alpha_weight'              => 0.15,
            'default_sell_orders_count' => 3,
            'performance_fee_percent'   => 22,
            'p2p_min_order_value'       => 5,
            'is_enabled'                => true,
            'transfer_fee_tiers'        => [
                ['from' => 20,   'to' => 100,  'fee_type' => 'flat',    'fee_value' => 1],
                ['from' => 100,  'to' => 1000, 'fee_type' => 'percent', 'fee_value' => 1],
                ['from' => 1000, 'to' => null, 'fee_type' => 'flat',    'fee_value' => 12],
            ],
        ]);
    }
}
