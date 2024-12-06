<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyChain;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencyChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $btc = Currency::where('symbol', 'BTC')->first();
        $eth = Currency::where('symbol', 'ETH')->first();
        $usdt = Currency::where('symbol', 'USDT')->first();
        $trx = Currency::where('symbol', 'TRX')->first();
        $bnb = Currency::where('symbol', 'BNB')->first();
        $doge = Currency::where('symbol', 'DOGE')->first();

        // Seed Currency Chains Data
        $currencyChains = [
            // BTC chains (Bitcoin)
            [
                'currency_id' => $btc->id,
                'chain' => 'BTC',
                'min_deposit_amount' => 0.001,
                'min_withdraw_amount' => 0.001,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 10,
                'safe_confirmations' => 6,
                'exchange_profit' => 0,
                'network_fee' => 0.0005,
                'withdrawal_precision' => 8,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            // ETH (ERC20) chains
            [
                'currency_id' => $eth->id,
                'chain' => 'ERC20',
                'min_deposit_amount' => 0.01,
                'min_withdraw_amount' => 0.01,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 12,
                'exchange_profit' => 0,
                'network_fee' => 0.01,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            // TRC20 chain for USDT
            [
                'currency_id' => $usdt->id,
                'chain' => 'TRC20',
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 1,
                'exchange_profit' => 0,
                'network_fee' => 3.1,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            [
                'currency_id' => $usdt->id,
                'chain' => 'ERC20',
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 12,
                'exchange_profit' => 0,
                'network_fee' => 3.1,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            // BNB (BSC) chain
            [
                'currency_id' => $bnb->id,
                'chain' => 'BSC',
                'min_deposit_amount' => 0.01,
                'min_withdraw_amount' => 0.01,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 15,
                'exchange_profit' => 0,
                'network_fee' => 0.01,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            // TRX (TRC) chain
            [
                'currency_id' => $trx->id,
                'chain' => 'TRC20',
                'min_deposit_amount' => 0.1,
                'min_withdraw_amount' => 0.1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 10,
                'exchange_profit' => 0,
                'network_fee' => 0.5,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
            // DOGE chain
            [
                'currency_id' => $doge->id,
                'chain' => 'DOGE',
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 12,
                'exchange_profit' => 0,
                'network_fee' => 1,
                'withdrawal_precision' => 8,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
            ],
        ];

        foreach ($currencyChains as $chainData) {
            CurrencyChain::create($chainData);
        }
    }
}
