<?php

namespace Database\Seeders;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use App\Models\Currency;
use App\Models\CurrencyChain;
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
                'chain' => CurrencyChainEnum::BTC,
                'chain_name' => 'Bitcoin',
                'blockchain_name' => CurrencyBlockChainNameEnum::BITCOIN,
                'min_deposit_amount' => 0.001,
                'min_withdraw_amount' => 0.001,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 10,
                'safe_confirmations' => 6,
                'exchange_withdrawal_fee' => 0.001,
                'network_fee' => 0.0005,
                'withdrawal_precision' => 8,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'explorer_address_url' => 'https://blockchair.com/bitcoin/address/{address}',
                'explorer_tx_url' => 'https://blockchair.com/bitcoin/transaction/{hash}',
                'is_base_coin' => true,
            ],
            // ETH (ERC20) chains
            [
                'currency_id' => $eth->id,
                'chain' => CurrencyChainEnum::ERC20,
                'chain_name' => 'Ethereum (ERC20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::ETHEREUM,
                'min_deposit_amount' => 0.01,
                'min_withdraw_amount' => 0.01,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 0.001,
                'network_fee' => 0.01,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,
                'explorer_address_url' => 'https://etherscan.io/address/{address}',
                'explorer_tx_url' => 'https://etherscan.io/tx/{hash}',
            ],

            // TRC20 chain for USDT
            [
                'currency_id' => $usdt->id,
                'chain' => CurrencyChainEnum::TRC20,
                'chain_name' => 'TRON (TRC20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::TRON,
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 1,
                'exchange_withdrawal_fee' => 0.5,
                'network_fee' => 3.1,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => false,
                'explorer_address_url' => 'https://tronscan.org/#/address/{address}',
                'explorer_tx_url' => 'https://tronscan.org/#/transaction/{hash}',
            ],
            [
                'currency_id' => $usdt->id,
                'chain' => CurrencyChainEnum::ERC20,
                'chain_name' => 'Ethereum (ERC20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::ETHEREUM,
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 0.5,
                'network_fee' => 3.1,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'explorer_address_url' => 'https://etherscan.io/address/{address}',
                'explorer_tx_url' => 'https://etherscan.io/tx/{hash}',
                'is_base_coin' => false,

            ],
            [
                'currency_id' => $usdt->id,
                'chain' => CurrencyChainEnum::BSC,
                'chain_name' => 'BSC (BEP20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::BINANCE,
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 0.5,
                'network_fee' => 3.1,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => false,
                'explorer_address_url' => 'https://bscscan.com/address/{address}',
                'explorer_tx_url' => 'https://bscscan.com/tx/{hash}',
            ],

            // BNB (BSC) chain
            [
                'currency_id' => $bnb->id,
                'chain' => CurrencyChainEnum::BSC,
                'chain_name' => 'BSC (BEP20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::BINANCE,
                'min_deposit_amount' => 0.01,
                'min_withdraw_amount' => 0.01,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 5,
                'safe_confirmations' => 15,
                'exchange_withdrawal_fee' => 0.001,
                'network_fee' => 0.01,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'explorer_address_url' => 'https://bscscan.com/address/{address}',
                'explorer_tx_url' => 'https://bscscan.com/tx/{hash}',
                'is_base_coin' => true,

            ],
            // TRX (TRC) chain
            [
                'currency_id' => $trx->id,
                'chain' => CurrencyChainEnum::TRC20,
                'chain_name' => 'TRON (TRC20)',
                'blockchain_name' => CurrencyBlockChainNameEnum::TRON,
                'min_deposit_amount' => 0.1,
                'min_withdraw_amount' => 0.1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 10,
                'exchange_withdrawal_fee' => 1.5,
                'network_fee' => 0.5,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,

                'explorer_address_url' => 'https://tronscan.org/#/address/{address}',
                'explorer_tx_url' => 'https://tronscan.org/#/transaction/{hash}',
            ],
            // DOGE chain
            [
                'currency_id' => $doge->id,
                'chain' => 'DOGE',
                'chain_name' => 'Dogecoin',
                'blockchain_name' => CurrencyBlockChainNameEnum::DOGE,
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 10,
                'network_fee' => 1,
                'withdrawal_precision' => 8,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,

                'explorer_address_url' => 'https://blockcypher.com/doge/address/{address}',
                'explorer_tx_url' => 'https://blockcypher.com/doge/tx/{hash}',
            ],
        ];

        foreach ($currencyChains as $chainData) {
            CurrencyChain::create($chainData);
        }
    }
}
