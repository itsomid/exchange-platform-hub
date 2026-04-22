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
        $pol = Currency::where('symbol', 'POL')->first();
        $arb = Currency::where('symbol', 'ARB')->first();
        $avax = Currency::where('symbol', 'AVAX')->first();
        $sonic = Currency::where('symbol', 'S')->first();

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
             // ETH (Arbitrum) chains
            [
                'currency_id' => $eth->id,
                'chain' => CurrencyChainEnum::ARBITRUM,
                'chain_name' => 'Arbitrum',
                'blockchain_name' => CurrencyBlockChainNameEnum::ARBITRUM,
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
                'explorer_address_url' => 'https://arbiscan.io/address/{address}',
                'explorer_tx_url' => 'https://arbiscan.io/tx/{hash}',
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
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
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
                'is_base_coin' => false,
                'explorer_address_url' => 'https://etherscan.io/address/{address}',
                'explorer_tx_url' => 'https://etherscan.io/tx/{hash}',
                'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7'
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
                'contract_address' => '0x55d398326f99059fF775485246999027B3197955'
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

                'explorer_address_url' => 'https://blockchair.com/dogecoin/address/{address}',
                'explorer_tx_url' => 'https://blockchair.com/dogecoin/transaction/{hash}',
            ],
            // POL (Polygon) chain
            [
                'currency_id' => $pol->id,
                'chain' => CurrencyChainEnum::POLYGON,
                'chain_name' => 'Polygon',
                'blockchain_name' => CurrencyBlockChainNameEnum::POLYGON,
                'min_deposit_amount' => 2,
                'min_withdraw_amount' => 6,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 128,
                'exchange_withdrawal_fee' => 0.5,
                'network_fee' => 0.001,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,
                'explorer_address_url' => 'https://polygonscan.com/address/{address}',
                'explorer_tx_url' => 'https://polygonscan.com/tx/{hash}',
            ],
            [
                'currency_id' => $arb->id,
                'chain' => CurrencyChainEnum::ARBITRUM,
                'chain_name' => 'Arbitrum',
                'blockchain_name' => CurrencyBlockChainNameEnum::ARBITRUM,
                'min_deposit_amount' => 2,
                'min_withdraw_amount' => 6,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 128,
                'exchange_withdrawal_fee' => 0.5,
                'network_fee' => 0.001,
                'withdrawal_precision' => 6,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,
                'explorer_address_url' => 'https://arbiscan.io/address/{address}',
                'explorer_tx_url' => 'https://arbiscan.io/tx/{hash}',
            ],
            // AVAX (Avalanche C-Chain)
            [
                'currency_id' => $avax->id,
                'chain' => CurrencyChainEnum::AVALANCHE,
                'chain_name' => 'Avalanche',
                'blockchain_name' => CurrencyBlockChainNameEnum::AVALANCHE,
                'min_deposit_amount' => 0.1,
                'min_withdraw_amount' => 0.1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 0.01,
                'network_fee' => 0.001,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,
                'explorer_address_url' => 'https://snowtrace.io/address/{address}',
                'explorer_tx_url' => 'https://snowtrace.io/tx/{hash}',
            ],
            // Sonic (S) chain
            [
                'currency_id' => $sonic->id,
                'chain' => CurrencyChainEnum::SONIC,
                'chain_name' => 'Sonic',
                'blockchain_name' => CurrencyBlockChainNameEnum::SONIC,
                'min_deposit_amount' => 1,
                'min_withdraw_amount' => 1,
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'deposit_delay_minutes' => 0,
                'safe_confirmations' => 12,
                'exchange_withdrawal_fee' => 0.01,
                'network_fee' => 0.001,
                'withdrawal_precision' => 18,
                'memo' => null,
                'is_memo_required_for_deposit' => false,
                'is_base_coin' => true,
                'explorer_address_url' => 'https://sonicscan.org/address/{address}',
                'explorer_tx_url' => 'https://sonicscan.org/tx/{hash}',
            ],
            
        ];

        foreach ($currencyChains as $chainData) {
            CurrencyChain::firstOrCreate(
                [
                    'currency_id' => $chainData['currency_id'],
                    'chain' => $chainData['chain'],
                    'contract_address' => $chainData['contract_address'] ?? null,
                ],
                $chainData
            );
        }
    }
}
