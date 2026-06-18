<?php

namespace App\Services\Bot;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\Bot\BotWalletDeposited;
use App\Events\Bot\BotWalletWithdrawn;
use App\Exceptions\Bot\InsufficientBotWalletException;
use App\Helpers\Math;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class BotWalletService
{
    private const USDT = 'USDT';

    public function __construct(
        private readonly FeeCalculator $feeCalculator,
    ) {}

    /**
     * Transfer USDT from the user's main wallet into their bot wallet.
     */
    public function transferIn(User $user, string $grossAmount): void
    {
        $fee = $this->feeCalculator->transferFee($grossAmount);

        DB::transaction(function () use ($user, $grossAmount, $fee) {
            $userWallet = $this->getUserUsdtWallet($user);
            $this->assertSufficientMainBalance($userWallet, $grossAmount);

            // Debit main wallet
            $userWallet->decrement('balance', $grossAmount);

            // Credit bot wallet (principal_balance/profit_balance are D1 placeholders for future reinvest)
            $botWallet = $this->getOrCreateBotWallet($user);
            $botWallet->update([
                'balance' => Math::add((string) $botWallet->balance, $grossAmount),
            ]);

            // Transaction: transfer out of main wallet
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => -$grossAmount,
                'balance'    => $userWallet->fresh()->balance,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_OUT,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'انتقال به ربات',
            ]);

            // Transaction: fee charged
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => -$fee,
                'balance'    => $userWallet->fresh()->balance,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_FEE,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'کارمزد انتقال به ربات',
            ]);

            event(new BotWalletDeposited($user->id, $grossAmount, $fee));
        });
    }

    /**
     * Transfer USDT from the bot wallet back to the user's main wallet.
     */
    public function transferOut(User $user, string $amount): void
    {
        $fee = $this->feeCalculator->withdrawFee($amount);
        $totalRequired = Math::add($amount, $fee);

        DB::transaction(function () use ($user, $amount, $fee, $totalRequired) {
            $botWallet = $this->getBotWalletWithLock($user);
            $this->assertSufficientBotBalance($botWallet, $totalRequired);

            $userWallet = $this->getUserUsdtWallet($user);

            // Debit bot wallet (principal_balance/profit_balance are D1 placeholders for future reinvest)
            $botWallet->update([
                'balance' => Math::sub((string) $botWallet->balance, $totalRequired),
            ]);

            // Credit main wallet
            $userWallet->increment('balance', $amount);

            // Transaction: transfer into main wallet
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => $amount,
                'balance'    => $userWallet->fresh()->balance,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_IN,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'برداشت از ربات',
            ]);

            // Transaction: fee
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => -$fee,
                'balance'    => $userWallet->fresh()->balance,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_FEE,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'کارمزد برداشت از ربات',
            ]);

            event(new BotWalletWithdrawn($user->id, $amount, $fee));
        });
    }

    public function getOrCreateBotWallet(User $user): BotWallet
    {
        return BotWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance'           => '0.00000000',
                'principal_balance' => '0.00000000',
                'profit_balance'    => '0.00000000',
                'locked_balance'    => '0.00000000',
            ]
        );
    }

    private function getBotWalletWithLock(User $user): BotWallet
    {
        $wallet = BotWallet::where('user_id', $user->id)->lockForUpdate()->first();

        if (! $wallet) {
            throw new InsufficientBotWalletException();
        }

        return $wallet;
    }

    private function getUserUsdtWallet(User $user): Wallet
    {
        return Wallet::where('user_id', $user->id)
            ->where('currency_symbol', self::USDT)
            ->firstOrFail();
    }

    private function assertSufficientMainBalance(Wallet $wallet, string $amount): void
    {
        if (Math::comp($wallet->available_balance, $amount) === -1) {
            throw new InsufficientBotWalletException();
        }
    }

    private function assertSufficientBotBalance(BotWallet $botWallet, string $required): void
    {
        if (Math::comp($botWallet->free_balance, $required) === -1) {
            throw new InsufficientBotWalletException();
        }
    }
}
