<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Deposit\DepositService;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws \Exception
     */
    public function run(): void
    {
        $walletService = new WalletService();
        $depositService = new DepositService();

        $user = User::find(2);

        $currencySymbol = 'USDT';
        $currencyChain = 'BEP20';

        $walletChain = $walletService->createDepositAddress(
            userId: $user->id,
            currencySymbol: $currencySymbol,
            currencyChain: $currencyChain
        );
        $amount = 55; // Example amount

        $transactionHash = 'txhash_' . bin2hex(random_bytes(10)); // Example transaction hash
        $note = 'Test deposit for seeding';

        $deposit = $depositService->createDeposit(
            userId: $user->id,
            currency_symbol: $currencySymbol,
            currency_chain: $currencyChain,
            amount: $amount,
            address: $walletChain->address,
            transaction_hash: $transactionHash,
            note: $note
        );


        $depositService->confirmDeposit($deposit->id);
    }
}
