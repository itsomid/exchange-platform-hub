<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;


use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Database\Seeder;

class WithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $withdrawalService = new WithdrawalService();

        // Step 1: Fetch or create a user
//        $users = User::where('id', '!=', 1)->take(3)->get();
        $user = User::find(2);

        $currencySymbol = 'BTC';
        $currencyChain = 'BTC';

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency_symbol' => $currencySymbol],
            ['balance' => 1.0, 'locked_balance' => 0] // Ensure the wallet has funds
        );


        // Step 3: Create a withdrawal request
        $amount = 0.05; // Example withdrawal amount
        $address = 'bc1qsl4egjdl8s3mw822mmakvzsup7sedkd4n5755d'; // Example BTC address

        try {
            $withdrawal = $withdrawalService->createWithdrawal(
                userId: $user->id,
                walletId: $wallet->id,
                currencyChain: $currencyChain,
                currencySymbol: $currencySymbol,
                amount: $amount,
                address: $address,
                description: 'Test withdrawal for seeding'
            );

            // Step 4: Confirm the withdrawal
//            $transactionHash = '44dbec29398e9844694d317928281ff36b873d93c07ab18bcfbb7b098a91523a'; // Example transaction hash
//            $confirmedWithdrawal = $withdrawalService->confirmWithdrawal($withdrawal->id, $transactionHash);

//            echo "Withdrawal created and confirmed: ID {$confirmedWithdrawal->id}, Hash: {$transactionHash}\n";
        } catch (\Exception $e) {
            echo "Error seeding withdrawal: " . $e->getMessage() . "\n";
        }
    }
}
