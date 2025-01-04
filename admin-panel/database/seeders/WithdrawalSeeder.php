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
    private WithdrawalService $withdrawalService;
    private string $currencySymbol = 'BTC';
    private string $currencyChain = 'BTC';
    private string $address = 'bc1qsl4egjdl8s3mw822mmakvzsup7sedkd4n5755d';
    public function __construct()
    {
        $this->withdrawalService = new WithdrawalService();
    }

    public function run(): void
    {
        $this->processWithdrawals(0.09, 0, 3, false);
        $this->processWithdrawals(0.05, 3, 3, true);
        $this->processWithdrawals(0.2, 6, 3, false);
    }

    private function processWithdrawals(float $amount, int $skip, int $limit, bool $confirm): void
    {
        $users = User::where('id', '>', 1)->skip($skip)->take($limit)->get();

        foreach ($users as $user) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency_symbol' => $this->currencySymbol],
                ['balance' => 1.0, 'locked_balance' => 0]
            );

            try {
                $withdrawal = $this->withdrawalService->createWithdrawal(
                    userId: $user->id,
                    walletId: $wallet->id,
                    currencyChain: $this->currencyChain,
                    currencySymbol: $this->currencySymbol,
                    amount: $amount,
                    address: $this->address,
                    description: 'Test withdrawal for seeding'
                );

                if ($confirm) {
                    $transactionHash = '44dbec29398e9844694d317928281ff36b873d93c07ab18bcfbb7b098a91523a' . rand(1, 100);
                    $this->withdrawalService->confirmWithdrawal($withdrawal->id, $wallet->id, $transactionHash);
                    echo "Withdrawal created and confirmed for User ID: {$user->id}, Hash: {$transactionHash}\n";
                } else {
                    echo "Withdrawal created for User ID: {$user->id}\n";
                }
            } catch (\Exception $e) {
                echo "Error processing withdrawal for User ID {$user->id}: " . $e->getMessage() . "\n";
            }
        }
    }
}
