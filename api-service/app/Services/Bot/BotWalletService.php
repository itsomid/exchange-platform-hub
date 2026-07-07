<?php

namespace App\Services\Bot;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\Bot\BotWalletDeposited;
use App\Events\Bot\BotWalletWithdrawn;
use App\Exceptions\Bot\BotAutoTradeActiveException;
use App\Exceptions\Bot\InsufficientBotWalletException;
use App\Helpers\Math;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BotWalletService
{
    private const USDT = 'USDT';
    protected $exchangeUserId;

    public function __construct(
        private readonly FeeCalculator $feeCalculator,
        private readonly WalletRepositoryInterface $walletRepository,
    ) {
            $this->exchangeUserId = config('bitexroom.user_id', 1);
    }

    /**
     * Transfer USDT from the user's main wallet into their bot wallet.
     */
    public function transferIn(User $user, string $grossAmount): void
    {
        $fee = $this->feeCalculator->transferFee($grossAmount);

        $netAmount = Math::sub($grossAmount, $fee);

        DB::transaction(function () use ($user, $grossAmount, $fee, $netAmount) {
            $userWallet = $this->getUserUsdtWallet($user);
            $this->assertSufficientMainBalance($userWallet, $grossAmount);

            $exchangeWallet = $this->walletRepository->getExchangeWallet('USDT');

            $userBalanceBefore = $userWallet->balance;
            $exchangeBalanceBefore = $exchangeWallet->balance;

            // Debit main wallet
            $userWallet->decrement('balance', $grossAmount);

            $exchangeWallet->increment('balance', $fee);

            // Credit bot wallet net of transfer fee (principal_balance/profit_balance are D1 placeholders)
            $botWallet = $this->getOrCreateBotWallet($user);
            $botWallet->update([
                'balance' => Math::add((string) $botWallet->balance, $netAmount),
            ]);

            // Transaction: transfer out of main wallet
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => -$grossAmount,
                'balance'    => $userBalanceBefore,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_IN,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز از کیف پول اصلی به کیف پول ربات',
            ]);

            // Transaction: fee charged
            Transaction::create([
                'user_id'    => $this->exchangeUserId,
                'wallet_id'  => $exchangeWallet->id,
                'amount'     => $fee,
                'balance'    => $exchangeBalanceBefore,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_FEE,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'کارمزد پرداختی کاربر (#' . $user->id . ') برای واریز از کیف پول اصلی به ربات',
           
            ]);

            event(new BotWalletDeposited($user->id, $grossAmount, $fee));
        });
    }

    /**
     * Transfer USDT from the bot wallet back to the user's main wallet.
     */
    public function transferOut(User $user, string $grossAmount): void
    {
        $this->assertAutoTradeDisabled($user);

        $fee = $this->feeCalculator->withdrawFee($grossAmount);
        $netAmount = Math::sub($grossAmount, $fee);

        DB::transaction(function () use ($user, $grossAmount, $fee, $netAmount) {
            $botWallet = $this->getBotWalletWithLock($user);
            $this->assertSufficientBotBalance($botWallet, $grossAmount);

            $userWallet = $this->getUserUsdtWallet($user);
            $exchangeWallet = $this->walletRepository->getExchangeWallet('USDT');

            $userBalanceBefore = $userWallet->balance;
            $exchangeBalanceBefore = $exchangeWallet->balance;

            // Debit bot wallet (principal_balance/profit_balance are D1 placeholders for future reinvest)
            $botWallet->update([
                'balance' => Math::sub((string) $botWallet->balance, $grossAmount),
            ]);

            // Credit main wallet
            $userWallet->increment('balance', $netAmount);

            $exchangeWallet->increment('balance', $fee);

            // Transaction: transfer into main wallet
            Transaction::create([
                'user_id'    => $user->id,
                'wallet_id'  => $userWallet->id,
                'amount'     => $netAmount,
                'balance'    => $userBalanceBefore,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_OUT,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'برداشت از کیف پول ربات به کیف پول اصلی',
            ]);

            // Transaction: fee
            Transaction::create([
                'user_id'    => $this->exchangeUserId,
                'wallet_id'  => $exchangeWallet->id,
                'amount'     => $fee,
                'balance'    => $exchangeBalanceBefore,
                'type'       => TransactionTypeEnum::BOT,
                'subtype'    => TransactionSubTypeEnum::BOT_TRANSFER_FEE,
                'status'     => TransactionStatusEnum::SUCCESS,
                'description' => 'کارمزد پرداختی کاربر (#' . $user->id . '). برای برداشت از کیف پول ربات',
            ]);

            event(new BotWalletWithdrawn($user->id, $grossAmount, $fee));
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

    /**
     * Withdrawals from the bot wallet are only allowed while auto-trade is off.
     * When it is on, freed funds are continuously reinvested, so the balance
     * must not be drained out from under an active buy/sell cycle.
     */
    private function assertAutoTradeDisabled(User $user): void
    {
        $autoTradeEnabled = BotUserSettings::where('user_id', $user->id)
            ->value('auto_trade_enabled');

        if ($autoTradeEnabled) {
            throw new BotAutoTradeActiveException();
        }
    }
}
