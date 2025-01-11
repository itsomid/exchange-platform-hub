<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{

    public function run()
    {
        \DB::table('settings')->insert([
            [
                'key' => 'otc_buy_fee',
                'name' => 'کارمزد خرید مشتری',
                'value' => '0.11',
            ],
            [
                'key' => 'otc_sell_fee',
                'name' => 'کارمزد فروش مشتری',
                'value' => '0.11',
            ],
            [
                'key' => 'referral_profit_status',
                'name' => 'وضعیت سیستم رفرال',
                'value' => true,
            ],
            [
                'key' => 'referral_profit_percentage',
                'name' => ' نرخ کارمزد اهدایی به کاربر از طریق کد دعوت',
                'value' => '30',
            ],
        ]);
    }

}
