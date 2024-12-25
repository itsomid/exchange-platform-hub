<?php

namespace Database\Seeders;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
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

        $user = User::factory()
            ->has(
                Wallet::factory(2)->sequence(['balance' => 1, 'currency_symbol' => 'BTC'], ['balance' => 10000, 'currency_symbol' => 'USDT']),
                'wallets'
            )
            ->create();

        $admin = User::query()->find(1);
        $adminWallet = Wallet::query()
            ->where('user_id', $admin->id)
            ->where('currency_symbol', 'BTC')
            ->first();
        //For OTC - Buy 0.1 BTC
        $amount = 0.1;
        //Withdraw from Bitexroom
        Transaction::query()->create([
            'user_id' => 1,
            'wallet_id' => $adminWallet->id,
            'amount' => $amount,
            'balance' => bcsub($adminWallet->balance, $amount, 8),
            'type' => TransactionTypeEnum::OTC_SELL,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'برداشت بیت کوین از حساب صرافی',
        ]);
        $adminWallet
            ->decrement('balance', $amount);
        //Deposit into user
        Transaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallets[0]->id,
            'amount' => $amount,
            'balance' =>  bcadd("1", $amount, 8),
            'type' => TransactionTypeEnum::OTC_BUY,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'واریز بیت کوین به حساب مشتری',
        ]);
        Wallet::query()
            ->where('id', $user->wallets[0]->id)
            ->decrement('balance', $amount);

        //FEE
        Transaction::query()->create([
            'user_id' => 1,
            'wallet_id' => $adminWallet->id,
            'amount' => $fee = bcmul($amount, Setting::getSetting('otc_buy_fee'), 8),
            'balance' => $fee = bcadd($adminWallet->balance, $fee, 8),
            'type' => TransactionTypeEnum::FEE,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'مبلغ کارمزد',
        ]);

        $usdt = Market::query()->where('base_currency', 'BTC')->first();
        $walletUSDT = Wallet::query()->where('user_id', $user->id)->where('currency_symbol', 'USDT')->first();
        //Withdraw USDT from user
        Transaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $walletUSDT->id,
            'amount' => $btcValue = bcmul($amount, $usdt->activeExchangePrice->price, 8),
            'balance' => bcadd($walletUSDT->balance, $btcValue, 8),
            'type' => TransactionTypeEnum::OTC_SELL,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'برداشت معادل تتری',

        ]);

        $walletUSDT
            ->decrement('balance', $btcValue);

        //Deposit USDT to bitexroom
        $adminUSDTWallet = Wallet::query()->where('user_id', 1)->where('currency_symbol', 'USDT')->first();
        Transaction::query()->create([
            'user_id' => 1,
            'wallet_id' => $adminUSDTWallet->id,
            'amount' => $btcValue,
            'balance' => bcadd($adminUSDTWallet->balance, $btcValue, 8),
            'type' => TransactionTypeEnum::DEPOSIT,
            'subtype' => TransactionSubTypeEnum::OTC,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => 'واریز مبلغ تتر از حساب مشتری به حساب صرافی',
        ]);

    }


}
