<?php

namespace App\Services\ReferralCode;

use App\Enums\OTCOrderTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\Math;
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

            $introducerCommission = Math::mul($exchangeFee, $introducerFeeRate);
            $friendCommission = Math::mul($exchangeFee, $friendFeeRate);

            // convert commission from base currency to usdt
            if ($otcOrder->type === OTCOrderTypeEnum::BUY) {
                $introducerCommissionToUSDT = Math::mul($introducerCommission, $otcOrder->price);
                $friendCommissionToUSDT = Math::mul($friendCommission, $otcOrder->price);
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

            return Math::sub($exchangeFee, Math::add($introducerCommission, $friendCommission));
        });
    }

    private function applyCommission(User $user, string $commissionAmount, OTCOrder $otcOrder, ReferralCode $referralCode, string $role): void
    {
        if ($commissionAmount <= 0) {
            return; // No commission to apply
        }

        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency_symbol', $otcOrder->market->quote_currency)->first();

        if (! $wallet) {
            return; // Wallet not found
        }

        $wallet->increment('balance', $commissionAmount);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otcOrder->id,
            'balance' => $wallet->balance,
            'amount' => $commissionAmount,
            'coin_price' => $otcOrder->price,
            'type' => TransactionTypeEnum::REFERRAL,
            'subtype' => $role === 'introducer' ? TransactionSubTypeEnum::REFERRAL_INTRODUCER : TransactionSubTypeEnum::REFERRAL_FRIEND,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "Referral commission ($role) from OTC order ID {$otcOrder->id}",
        ]);

        ReferralCodeUsage::query()->create([
            'referral_code_id' => $referralCode->id,
            'used_by' => $otcOrder->user_id,
            'transaction_id' => $transaction->id,
            'type' => $role,
            'used_at' => now(),
        ]);

        $exchangeWallet = $this->walletRepository->getBitexroomWallet('USDT');
        if ($exchangeWallet) {
            $exchangeWallet->decrement('balance', $commissionAmount);

            Transaction::query()->create([
                'user_id' => config('bitexroom.user_id'), // Admin or exchange user ID
                'wallet_id' => $exchangeWallet->id,
                'otc_order_id' => $otcOrder->id,
                'balance' => $exchangeWallet->balance,
                'amount' => -$commissionAmount,
                'type' => TransactionTypeEnum::REFERRAL,
                'subtype' => $role === 'introducer' ? TransactionSubTypeEnum::REFERRAL_INTRODUCER : TransactionSubTypeEnum::REFERRAL_FRIEND,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "Referral commission ($role) expense from OTC order ID {$otcOrder->id}",
            ]);
        }
    }
}
