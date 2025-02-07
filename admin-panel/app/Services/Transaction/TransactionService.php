<?php

namespace App\Services\Transaction;

use App\Enums\BalanceOperationEnum;
use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Models\Admin;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Deposit\DepositService;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Services\Wallet\WalletService;

class TransactionService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    public function     increaseDecreaseAdminWalletCredit(
        int     $userId,
        float   $amount,
        Currency  $currency,
        CurrencyChain  $currencyChain,
        string  $type, // INCREASE or DECREASE
        ?int    $adminId = null,
        ?string $description = null,
        ?string $admin_description = null
    ): void {
        \DB::transaction(function () use ($userId, $amount, $currency, $currencyChain, $type, $adminId, $description, $admin_description) {
            // Fetch the wallet
            $exchangeWallet = $this->walletService->getExchangeWallet($currency->symbol);
            if (!$exchangeWallet) {
                return redirect()->back()->withErrors(['wallet' => 'کیف پول مورد نظر یافت نشد.']);
            }

            if ($type === TransactionTypeEnum::WITHDRAWAL->value && $exchangeWallet->balance < $amount) {
                throw new \Exception('موجودی کیف پول کافی نیست.');
            }

            if ($type === TransactionTypeEnum::DEPOSIT->value) {

                $deposit = Deposit::create([
                    'user_id' => $userId,
                    'currency_symbol' => $currency->symbol,
                    'currency_chain_id' => $currencyChain->id,
                    'amount' => $amount,
                    'usdt_value' => $amount * $currency->exchangePrice,
                    'address' => null,
                    'transaction_hash' => null,
                    'description' => 'Exchange Wallet credit increase by admin: (#' . $adminId . ') '.Admin::find($adminId)->fullname(),
                    'status' => DepositStatusEnum::CONFIRMED,
                ]);
                $this->logTransaction(
                    wallet: $exchangeWallet,
                    amount: $amount,
                    type: TransactionTypeEnum::DEPOSIT->value,
                    adminId: $adminId,
                    depositId: $deposit->id, // No deposit ID for admin direct actions
                    description: 'Exchange Wallet credit increase',
                    admin_description: $admin_description
                );
                $exchangeWallet->increment('balance', $amount);

            } else {

                $withdraw = Withdrawal::create([
                    'user_id' => $userId,
                    'currency_symbol' => $currency->symbol,
                    'currency_chain_id' => $currencyChain->id,
                    'amount' => $amount,
                    'usdt_value' => $amount * $currency->exchangePrice,
                    'address' => null,
                    'transaction_hash' => null,
                    'description' => 'Exchange Wallet deduction by admin: (#' . $adminId . ') '.Admin::find($adminId)->fullname(),
                    'status' => WithdrawalStatusEnum::COMPLETED,
                ]);
                $this->logTransaction(
                    wallet: $exchangeWallet,
                    amount: $amount,
                    type: TransactionTypeEnum::DEPOSIT->value,
                    adminId: $adminId,
                    withdrawalId: $withdraw->id, // No deposit ID for admin direct actions
                    description: 'Exchange Wallet credit decrease',
                    admin_description: $admin_description
                );

                $exchangeWallet->decrement('balance', $amount);
            }


            // Log the transaction

        });
    }
    /**
     * Transfer funds between exchange wallet and user wallet.
     *
     * @param int $fromUserId
     * @param int $toUserId
     * @param float $amount
     * @param string $currency
     * @param string $type
     * @param string|null $context
     * @param string|null $description
     * @param string|null $admin_description
     * @param int|null $adminId
     * @throws \Exception
     */
    public function transferBetweenWallets(
        int     $fromUserId,
        int     $toUserId,
        float   $amount,
        Currency  $currency,
        CurrencyChain  $currencyChain,
        string  $type,
        ?int    $adminId = null,
        ?string $description = null,
        ?string $admin_description = null
    ): void
    {

        \DB::transaction(function () use ($fromUserId, $toUserId, $amount, $currency, $currencyChain, $type, $adminId, $description, $admin_description) {
            // Fetch wallets

            $fromWallet = Wallet::firstOrCreate(
                ['user_id' => $fromUserId, 'currency_symbol' => $currency->symbol],
                ['balance' => 0]
            );

            $toWallet = Wallet::firstOrCreate(
                ['user_id' => $toUserId, 'currency_symbol' => $currency->symbol],
                ['balance' => 0]
            );

            // Validate sufficient balance in the from wallet
            if ($fromWallet->balance < $amount) {
                if ($fromUserId === 1) {
                    throw new \Exception('موجودی ناکافی کیف پول صرافی برای برداشت.');
                } else {
                    throw new \Exception('موجودی ناکافی کیف پول مبدا برای برداشت.');
                }

            }

            $deposit = Deposit::create([
                'user_id' => $type === TransactionTypeEnum::DEPOSIT->value ? $toUserId : $fromUserId,
                'currency_symbol' => $currency->symbol,
                'currency_chain_id' => $currencyChain->id,
                'amount' => $amount,
                'usdt_value' => $amount * $currency->exchangePrice,
                'address' => null,
                'transaction_hash' => null,
                'description' => 'manual transfer by admin: (#' . $adminId . ') to: (#' . User::find($toUserId)->username . ')',
                'status' => DepositStatusEnum::CONFIRMED,
            ]);
            $withdrawal = Withdrawal::create([
                'user_id' => $type === TransactionTypeEnum::WITHDRAWAL->value ? $toUserId : $fromUserId,
                'currency_symbol' => $currency->symbol,
                'currency_chain_id' => $currencyChain->id,
                'amount' => $amount,
                'usdt_value' => $amount * $currency->exchangePrice,
                'address' => null,
                'transaction_hash' => null,
                'description' => 'manual transfer by admin: (#' . $adminId . ') to: (#' . User::find($toUserId)->username . ')',
                'status' => WithdrawalStatusEnum::COMPLETED,
            ]);



            $this->logTransaction(
                wallet: $fromWallet,
                amount: -$amount,
                type:  TransactionTypeEnum::WITHDRAWAL->value,
                adminId: $adminId,
                withdrawalId: $withdrawal->id, // No deposit ID for admin direct actions
                description: $description ?? 'Funds withdrawn.',
                admin_description: $admin_description
            );
            $fromWallet->decrement('balance',$amount);


            $this->logTransaction(
                wallet: $toWallet,
                amount: $amount,
                type: TransactionTypeEnum::DEPOSIT->value,
                adminId: $adminId,
                depositId: $deposit->id, // No deposit ID for admin direct actions
                description: $description ?? 'Funds deposited.',
                admin_description: $admin_description
            );

            $toWallet->increment('balance',$amount);
        });
    }


    /**
     * Log a transaction for a wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string $type

     * @param string|null $description
     * @param string|null $admin_description
     * @return void
     */
    private function logTransaction(
        Wallet $wallet,
        float $amount,
        string $type,
        ?int $adminId,
        ?int $depositId = null,
        ?int $withdrawalId = null,
        ?string $description = null,
        ?string $admin_description = null
    ): void
    {
        Transaction::create([
            'user_id' => $wallet->user_id,
            'admin_id' => $adminId,
            'wallet_id' => $wallet->id,
            'deposit_id' => $depositId,
            'withdrawal_id' => $withdrawalId,
            'amount' => $amount,
            'balance' => $wallet->balance,
            'type' => $type,
            'subtype' => TransactionSubTypeEnum::MANUAL_ADMIN,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => $description,
            'admin_description' => $admin_description
        ]);
    }


    //report////
    public function totalTransactionsBasedType( Int $userId, string $currencySymbol, array $transactionTypes): float
    {
        // Get the total amount of deposits for the given currency
        $totalTransactions = Transaction::whereIn('type', $transactionTypes)
            ->whereUserId($userId)
            ->whereHas('wallet', function ($query) use ($currencySymbol) {
                $query->where('currency_symbol', $currencySymbol);
            })
            ->sum('amount');

        return abs($totalTransactions);
    }
    public function totalTransactionsValueBasedType( Int $userId, string $currencySymbol, array $transactionTypes): float
    {
        // Get the total amount of deposits for the given currency
        $totalTransactions = Transaction::whereIn('type', $transactionTypes)
            ->whereUserId($userId)
            ->whereHas('wallet', function ($query) use ($currencySymbol) {
                $query->where('currency_symbol', $currencySymbol);
            })
            ->sum('amount');

        // Fetch the exchange rate for the currency
        $currency = Currency::where('symbol', $currencySymbol)->first();

        // Calculate the value in USDT
        return abs($totalTransactions * $currency->exchangePrice);
    }

}
