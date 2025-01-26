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
            [
                'key' => 'BNB_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => '0x5b685Bb78B229B41C3E854D8719062FeebC2BA3b',
            ],
            [
                'key' => 'DOGE_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => 'DGvNZMe5TRmEojvaJj69Mv6hzL6TySKuuq',
            ],
            [
                'key' => 'TRON_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => 'TJ6vTNSJhhWsMai2M69YDwMQSyGfn75yXc',
            ],
            [
                'key' => 'ETHEREUM_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => '0x5b685Bb78B229B41C3E854D8719062FeebC2BA3b',
            ],
            [
                'key' => 'BITCOIN_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => 'bc1q48dxdvv92vytg3cjtvy66umq44gd06sr3h8757',
            ],
        ]);
    }

}
