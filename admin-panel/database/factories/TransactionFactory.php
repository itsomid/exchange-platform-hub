<?php

namespace Database\Factories;

use App\Enums\BalanceOperationEnum;
use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
        ];
    }

    public function withReferralCodeUsage()
    {

        $user_id =  ReferralCode::query()
            ->inRandomOrder()
            ->value('user_id');

        return $this->state(function (array $attributes) use ($user_id) {
            $wallet = Wallet::query()
                ->where('user_id', $user_id)
                ->where('currency_symbol', 'USDT')
                ->first();
            $amount = $this->faker->randomFloat(2, 2, 5);
            $newBalance = $wallet ? bcadd($wallet->balance  ,$amount,8): $amount;

            return [
                'user_id' => $user_id,
                'wallet_id'=>$wallet->id,
                'type' => 'referral', // Creates a referral code and assigns it
                'status' => 'completed',
                'amount' => $amount,
                'balance' => $newBalance,
                'description' => 'charge for referral',
            ];
        })->afterCreating(function (Transaction $transaction) use ($user_id) {

            $walletService = resolve(WalletService::class);
            $walletService->updateBalance(
                resolve(UpdateBalanceRequestDTO::class)
                    ->setAmount($transaction->amount)
                    ->setOperation(BalanceOperationEnum::INCREASE)
                    ->setCurrencySymbol('USDT')
                    ->setUserId($user_id)
            );

            $wallet = Wallet::query()
                ->where('user_id', $user_id)
                ->where('currency_symbol', 'USDT')
                ->first();

            if ($wallet) {
                $transaction->update([
                    'balance' => $wallet->balance, // Ensure the transaction reflects the correct updated balance
                ]);
            }
            $referralCode = ReferralCode::query()
                ->where('user_id', $user_id)
                ->first();
            $used_by = User::query()->where('introducer_code', $referralCode->id)->first();
            if ($referralCode && $used_by) {
                ReferralCodeUsage::create([
                    'referral_code_id' => $referralCode->id,
                    'used_by' => $used_by->id,
                    'transaction_id' => $transaction->id,
                    'used_at' => now(),
                ]);
            }
        });
    }
}
