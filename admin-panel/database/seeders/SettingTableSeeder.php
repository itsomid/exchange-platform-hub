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
                'name' => 'نرخ کارمزد اهدایی به کاربر از طریق کد دعوت',
                'value' => '30',
            ],
            [
                'key' => 'referral_usage_limit_count',
                'name' => 'حداکثر تعداد استفاده کاربر از کد دعوت',
                'value' => '50',
            ],
            [
                'key' => 'exchange_withdrawal_period_time',
                'name' => 'پارامتر زمان برای برداشت از صرافی مرجع',
                'value' => '60',
            ],
            [
                'key' => 'exchange_withdrawal_period_buy',
                'name' => 'پارامتر تعداد خرید برای برداشت از صرافی مرجع',
                'value' => '5',
            ],
            [
                'key' => 'exchange_withdrawal_type',
                'name' => '(زمان/تعداد) مدل تجمیع و برداشت از صرافی مرجع',
                'value' => 'exchange_withdrawal_period_time',
            ],
            [
                'key' => 'exchange_withdrawal_status',
                'name' => '(فعال/غیرفعال) وضعیت سیستم تجمیع دارایی',
                'value' => false,
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
                'key' => 'TRX_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => 'TJ6vTNSJhhWsMai2M69YDwMQSyGfn75yXc',
            ],
            [
                'key' => 'ETH_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => '0x5b685Bb78B229B41C3E854D8719062FeebC2BA3b',
            ],
            [
                'key' => 'BTC_PUB_KEY',
                'name' => 'آدرس pubkey ولت BNB در HD Wallet',
                'value' => 'bc1q48dxdvv92vytg3cjtvy66umq44gd06sr3h8757',
            ],
            [
                'key' => 'USDT_PUB_KEY',
                'name' => 'آدرس pubkey ولت USDT در HD Wallet',
                'value' => 'TJ6vTNSJhhWsMai2M69YDwMQSyGfn75yXc',
            ]
        ]);
    }

}
