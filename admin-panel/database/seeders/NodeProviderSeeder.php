<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\NodeProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NodeProviderSeeder extends Seeder
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

        $nodeProviders = [
            // BTC
            [
                'currency_id' => $btc->id, // Replace with the actual BTC currency ID
                'name' => 'Blockstream',
                'base_url' => 'https://blockstream.info/api',
                'api_key' => null,
                'priority' => 1,
                'is_active' => true,
            ],
            // ETH
            [
                'currency_id' => $eth->id, // Replace with the actual ETH currency ID
                'name' => 'Infura',
                'base_url' => 'https://mainnet.infura.io/v3',
                'api_key' => 'your-infura-api-key',
                 'priority' => 1,
                'is_active' => true,
            ],
            [
                'currency_id' => $eth->id, // ETH again
                'name' => 'Alchemy',
                'base_url' => 'https://eth-mainnet.alchemyapi.io/v2',
                'api_key' => 'your-alchemy-api-key',
                 'priority' => 2,
                'is_active' => true,
            ],
            // USDT
            [
                'currency_id' => $usdt->id, // Replace with the actual USDT currency ID
                'name' => 'Etherscan',
                'base_url' => 'https://api.etherscan.io/api',
                'api_key' => 'your-etherscan-api-key',
                 'priority' => 1,
                'is_active' => true,
            ],
            // DOGE
            [
                'currency_id' => $doge->id, // Replace with the actual DOGE currency ID
                'name' => 'BlockCypher',
                'base_url' => 'https://api.blockcypher.com/v1/doge',
                'api_key' => 'your-blockcypher-api-key',
                 'priority' => 1,
                'is_active' => true,
            ],
            // BNB
            [
                'currency_id' => $bnb->id, // Replace with the actual BNB currency ID
                'name' => 'Binance Chain',
                'base_url' => 'https://bsc-dataseed.binance.org',
                 'priority' => 1,
                'api_key' => null,
                'is_active' => true,
            ],
            // TRON
            [
                'currency_id' => $trx->id, // Replace with the actual TRON currency ID
                'name' => 'TronGrid',
                'base_url' => 'https://api.trongrid.io',
                'api_key' => null,
                 'priority' => 1,
                'is_active' => true,
            ],
        ];

        // Insert into the database
        foreach ($nodeProviders as $provider) {
            NodeProvider::create($provider);
        }
    }
}
