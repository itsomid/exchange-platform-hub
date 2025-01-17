<?php

namespace App\Services\Referral;

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

class ReferralCommissionService
{
    public function processReferralCommission(OTCOrder $otcOrder, float $exchangeFee)
    {
        return \DB::transaction(function () use ($otcOrder, $exchangeFee) {


            $userIntroducerCode = $otcOrder->user->introducer_code;

            if (!$userIntroducerCode) {
                return null; // No referral code used
            }

            $referralCode = ReferralCode::find($userIntroducerCode);

            $introducer = User::find($referralCode->user_id);
            $friend = User::find($otcOrder->user_id);

            if (!$introducer || !$friend) {
                return null; // Invalid user references
            }

            $introducerFeeRate = $referralCode->introducer_fee / 100;
            $friendFeeRate = $referralCode->friend_fee / 100;

            $introducerCommission = bcmul($exchangeFee, $introducerFeeRate, 8);
            $friendCommission = bcmul($exchangeFee, $friendFeeRate, 8);


            // convert commission from base currency to usdt
            if ($otcOrder->type === OTCOrderTypeEnum::BUY) {

                $introducerCommission = bcmul($introducerCommission, $otcOrder->price,8);
                $friendCommission = bcmul($friendCommission, $otcOrder->price, 8);

            }



            $exchangeRemainingFee = bcsub($exchangeFee, bcadd($introducerCommission, $friendCommission, 8), 8);

            if ($introducerCommission > 0) {
                $this->applyCommission($introducer, $introducerCommission, $otcOrder, 'introducer');
            }

            if ($friendCommission > 0) {
                $this->applyCommission($friend, $friendCommission, $otcOrder, 'friend');
            }

            return $exchangeRemainingFee;
        });
    }

    private function applyCommission(User $user, float $amount, OTCOrder $otcOrder, string $role)
    {
        if ($amount <= 0) {
            return; // No commission to apply
        }

        $wallet = Wallet::where('user_id', $user->id)->where('currency_symbol', $otcOrder->market->quote_currency)->first();

        if (!$wallet) {
            return; // Wallet not found
        }

        $wallet->increment('balance', $amount);

        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'otc_order_id' => $otcOrder->id,
            'balance' => $wallet->balance,
            'amount' => $amount,
            'type' => TransactionTypeEnum::FEE,
            'subtype' => $role === 'introducer' ? TransactionSubTypeEnum::REFERRAL_INTRODUCER : TransactionSubTypeEnum::REFERRAL_FRIEND,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => "Referral commission ($role) from OTC order ID {$otcOrder->id}",
        ]);
    }
}

