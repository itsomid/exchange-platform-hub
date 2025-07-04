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
use App\Services\Wallet\WalletService;

class ReferralCommissionService
{

    protected $walletService;
    protected int $bitexroomUserId;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
        $this->bitexroomUserId = config('bitexroom.user_id');
    }
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
                $introducerCommissionToUSDT = bcmul($introducerCommission, $otcOrder->price,8);
                $friendCommissionToUSDT = bcmul($friendCommission, $otcOrder->price, 8);
                if ($introducerCommission > 0) {
                    $this->applyCommission($introducer, $introducerCommissionToUSDT, $otcOrder,$referralCode, 'introducer');
                }

                if ($friendCommission > 0) {
                    $this->applyCommission($friend, $friendCommissionToUSDT, $otcOrder,$referralCode, 'friend');
                }
            }else{

                if ($introducerCommission > 0) {
                    $this->applyCommission($introducer, $introducerCommission, $otcOrder,$referralCode, 'introducer');
                }

                if ($friendCommission > 0) {
                    $this->applyCommission($friend, $friendCommission, $otcOrder,$referralCode, 'friend');
                }
            }


            $exchangeRemainingFee = bcsub($exchangeFee, bcadd($introducerCommission, $friendCommission, 8), 8);

            return $exchangeRemainingFee;
        });
    }

    private function applyCommission(User $user, float $amount, OTCOrder $otcOrder,ReferralCode $referralCode, string $role)
    {
        if ($amount <= 0) {
            return; // No commission to apply
        }

        $wallet = Wallet::where('user_id', $user->id)->where('currency_symbol', $otcOrder->market->quote_currency)->first();

        if (!$wallet) {
            return; // Wallet not found
        }

        $wallet->increment('balance', $amount);

        $transaction = Transaction::create([
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

        ReferralCodeUsage::create([
            'referral_code_id' => $referralCode->id,
            'used_by' => $otcOrder->user->id,
            'transaction_id' => $transaction->id,
            'used_at' => now(),
        ]);

        $exchangeWallet = $this->walletService->getExchangeWallet('USDT');
        if ($exchangeWallet) {
            $exchangeWallet->decrement('balance', $amount);

            Transaction::create([
                'user_id' =>  $this->bitexroomUserId, // Admin or exchange user ID
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

