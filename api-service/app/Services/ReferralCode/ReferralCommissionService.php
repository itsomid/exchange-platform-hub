<?php

namespace App\Services\ReferralCode;

use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\OTCOrder;
use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReferralCommissionService
{
    public function __construct(private readonly WalletRepositoryInterface $walletRepository) {}

    public function processReferralCommission(OTCOrder $otcOrder, string $exchangeFee)
    {
        return DB::transaction(function () use ($otcOrder, $exchangeFee) {

            $userIntroducerCode = $otcOrder->user->introducer_code;

            if (! $userIntroducerCode) {
                return null; // No referral code used
            }

            $referralCode = ReferralCode::query()->find($userIntroducerCode);

            $introducer = User::query()->find($referralCode->user_id);
            $friend = User::query()->find($otcOrder->user_id);

            if (! $introducer || ! $friend) {
                return null; // Invalid user references
            }

            $introducerFeeRate = $referralCode->introducer_fee / 100;
            $friendFeeRate = $referralCode->friend_fee / 100;

            $introducerCommission = bcmul($exchangeFee, (string) $introducerFeeRate, 8);
            $friendCommission = bcmul($exchangeFee, (string) $friendFeeRate, 8);

            // convert commission from base currency to usdt
            if ($otcOrder->type === OTCOrderTypeEnum::BUY) {
                $introducerCommissionToUSDT = bcmul($introducerCommission, $otcOrder->price, 8);
                $friendCommissionToUSDT = bcmul($friendCommission, $otcOrder->price, 8);
                if ($introducerCommission > 0) {
                    $this->applyCommission($introducer, $introducerCommissionToUSDT, $otcOrder, $referralCode, 'introducer');
                }

                if ($friendCommission > 0) {
                    $this->applyCommission($friend, $friendCommissionToUSDT, $otcOrder, $referralCode, 'friend');
                }
            } else {

                if ($introducerCommission > 0) {
                    $this->applyCommission($introducer, $introducerCommission, $otcOrder, $referralCode, 'introducer');
                }

                if ($friendCommission > 0) {
                    $this->applyCommission($friend, $friendCommission, $otcOrder, $referralCode, 'friend');
                }
            }

            return bcsub($exchangeFee, bcadd($introducerCommission, $friendCommission, 8), 8);
        });
    }

    private function applyCommission(User $user, string $amount, OTCOrder $otcOrder, ReferralCode $referralCode, string $role): void
    {
        if ($amount <= 0) {
            return; // No commission to apply
        }

        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency_symbol', $otcOrder->market->quote_currency)->first();

        if (! $wallet) {
            return; // Wallet not found
        }

        $wallet->increment('balance', $amount);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otcOrder->id,
            'balance' => $wallet->balance,
            'amount' => $amount,
            'type' => TransactionTypeEnum::REFERRAL,
            'subtype' => $role === 'introducer' ? TransactionSubTypeEnum::REFERRAL_INTRODUCER : TransactionSubTypeEnum::REFERRAL_FRIEND,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "Referral commission ($role) from OTC order ID {$otcOrder->id}",
        ]);

        ReferralCodeUsage::query()->create([
            'referral_code_id' => $referralCode->id,
            'used_by' => $user->id,
            'transaction_id' => $transaction->id,
            'used_at' => now(),
        ]);

        $exchangeWallet = $this->walletRepository->getBitexroomWallet('USDT');
        if ($exchangeWallet) {
            $exchangeWallet->decrement('balance', $amount);

            Transaction::query()->create([
                'user_id' => config('bitexroom.bitexroom_user_id'), // Admin or exchange user ID
                'wallet_id' => $exchangeWallet->id,
                'otc_order_id' => $otcOrder->id,
                'balance' => $exchangeWallet->balance,
                'amount' => -$amount,
                'type' => TransactionTypeEnum::REFERRAL,
                'subtype' => $role === 'introducer' ? TransactionSubTypeEnum::REFERRAL_INTRODUCER : TransactionSubTypeEnum::REFERRAL_FRIEND,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "Referral commission ($role) expense from OTC order ID {$otcOrder->id}",
            ]);
        }
    }
}
