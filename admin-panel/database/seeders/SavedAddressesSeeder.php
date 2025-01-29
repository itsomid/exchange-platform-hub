<?php

namespace Database\Seeders;

use App\Enums\CurrencyChainEnum;
use App\Models\SavedAddress;
use Illuminate\Database\Seeder;

class SavedAddressesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $userId = 4; // User ID for whom the addresses will be saved

        $savedAddresses = [
            [
                'user_id' => $userId,
                'name' => 'Binance USDT Wallet',
                'chain' => CurrencyChainEnum::BEP20,
                'address' => '0x123456789abcdef123456789abcdef123456789a',
            ],
            [
                'user_id' => $userId,
                'name' => 'Ethereum USDT Wallet',
                'chain' => CurrencyChainEnum::ERC20,
                'address' => '0xabcdef123456789abcdef123456789abcdef1234',
            ],
            [
                'user_id' => $userId,
                'name' => 'Tron USDT Wallet',
                'chain' => CurrencyChainEnum::TRC20,
                'address' => 'TD123456789abcdef123456789abcdef123456789',
            ],
            [
                'user_id' => $userId,
                'name' => 'Ethereum Wallet',
                'chain' => CurrencyChainEnum::ERC20,
                'address' => '0xethwalletabcdef123456789abcdef123456789b',
            ],
            [
                'user_id' => $userId,
                'name' => 'Bitcoin Wallet',
                'chain' => CurrencyChainEnum::BTC,
                'address' => 'bc1qxyz123456789abcdef123456789abcdef12345',
            ],
        ];

        foreach ($savedAddresses as $address) {
            SavedAddress::create($address);
        }
    }
}
