<?php

namespace Database\Seeders;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Admin;
use App\Models\Market;
use App\Models\ReferralCode;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::factory(5)->withReferralCodeUsage()->create();


        $admin = User::query()->where('id', 1)->with('wallets')->first();

        $user = User::factory()->has(
            Wallet::factory(2)->sequence(
                ['currency_symbol' => 'BTC', 'balance' => 0],
                ['currency_symbol' => 'USDT', 'balance' => 10000]
            )
        )->create();

        // Buy BTC with USDT
        //BTC Transaction
        $amount = 0.1;
        Transaction::query()
            ->create([
                'user_id' => $user->id,
                'wallet_id' => $user->wallets()->where('currency_symbol', 'BTC')->first()->id,
                'amount' => $amount,
                'fee' => $fee = bcmul(Setting::getSetting('otc_buy_fee'), $amount, 8),
                'total' => $total = bcsub($amount, $fee, 8),
                'balance' => $user->wallets->where('currency_symbol', 'BTC')->first()->balance,//Before create transaction
                'type' => TransactionTypeEnum::OTC_BUY,
                'subtype' => TransactionSubTypeEnum::OTC,
                'description' => 'خرید بیت کوین با تتر',
                'status' => TransactionStatusEnum::SUCCESS,
            ]);
        //Withdraw BTC Bitexroom exchange
        Transaction::query()
            ->create([
                'user_id' => $admin->id,
                'wallet_id' => $admin->wallets()->where('currency_symbol', 'BTC')->first()->id,
                'amount' => $amount,
                'fee' => 0,
                'total' => $total,
                'balance' => $admin->wallets->where('currency_symbol', 'BTC')->first()->balance,//Before create transaction
                'type' => TransactionTypeEnum::OTC_SELL,
                'subtype' => TransactionSubTypeEnum::OTC,
                'description' => 'فروش بیت کوین به مشتری',
                'status' => TransactionStatusEnum::SUCCESS,
            ]);
        //Deposit user's USDT into Bitexroom exchange
        //calculate BTC value in USDT
        $market=  Market::query()->where('base_currency','BTC')->first();
        $amountUSDT = bcmul($market->activeExchangePrice->price, $amount, 8);
        Transaction::query()
            ->create([
                'user_id' => $user->id,
                'wallet_id' => $user->wallets()->where('currency_symbol', 'USDT')->first()->id,
                'amount' => $amountUSDT,
                'fee' => 0,
                'total' => $amountUSDT,
                'balance' => $user->wallets()->where('currency_symbol', 'USDT')->first()->balance,//Before create transaction
                'type' => TransactionTypeEnum::DEPOSIT,
                'subtype' => TransactionSubTypeEnum::OTC,
                'description' => 'واریز تتر مشتری بابت خرید بیت کوین',
                'status' => TransactionStatusEnum::SUCCESS,
            ]);
    }


}
