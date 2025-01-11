<?php

namespace App\Services\Transaction;

use App\Enums\BalanceOperationEnum;
use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Models\Currency;
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
    public const int EXCHANGE_USER_ID = 1; // Exchange wallet user ID

    public function __construct(private WalletService $walletService)
    {
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
        string  $currency,
        string  $currencyChain,
        string  $type,
        ?string $subtype = null,
        ?int    $adminId = null,
        ?string $description = null,
        ?string $admin_description = null
    ): void
    {

        \DB::transaction(function () use ($fromUserId, $toUserId, $amount, $currency, $currencyChain, $type, $subtype, $adminId, $description, $admin_description) {
            // Fetch wallets

            $fromWallet = Wallet::firstOrCreate(
                ['user_id' => $fromUserId, 'currency_symbol' => $currency],
                ['balance' => 0]
            );

            $toWallet = Wallet::firstOrCreate(
                ['user_id' => $toUserId, 'currency_symbol' => $currency],
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
                'currency_symbol' => $currency,
                'currency_chain' => $currencyChain,
                'amount' => $amount,
                'address' => null,
                'transaction_hash' => null,
                'description' => 'manual transfer by admin: (#' . $adminId . ') to: (#' . User::find($toUserId)->username . ')',
                'status' => DepositStatusEnum::CONFIRMED,
            ]);
            $withdrawal = Withdrawal::create([
                'user_id' => $type === TransactionTypeEnum::WITHDRAWAL->value ? $toUserId : $fromUserId,
                'currency_symbol' => $currency,
                'currency_chain' => $currencyChain,
                'amount' => $amount,
                'address' => null,
                'transaction_hash' => null,
                'description' => 'manual transfer by admin: (#' . $adminId . ') to: (#' . User::find($toUserId)->username . ')',
                'status' => WithdrawalStatusEnum::COMPLETED,
            ]);
            $depositId = $deposit->id;
            $withdrawalId = $withdrawal->id;


            // Update balances using WalletService
            $this->walletService->updateBalance(
                resolve(UpdateBalanceRequestDTO::class)
                    ->setAmount($amount)
                    ->setOperation(BalanceOperationEnum::DECREASE)
                    ->setCurrencySymbol($currency)
                    ->setUserId($fromUserId)
            );

            $fromWallet->refresh();
            $this->logTransaction(
                $fromWallet,
                -$amount,
                TransactionTypeEnum::WITHDRAWAL->value,
                $subtype,
                $adminId,
                null,
                $withdrawalId,
                $description ?? 'Funds withdrawn.',
                $admin_description
            );

            $this->walletService->updateBalance(
                resolve(UpdateBalanceRequestDTO::class)
                    ->setAmount($amount)
                    ->setOperation(BalanceOperationEnum::INCREASE)
                    ->setCurrencySymbol($currency)
                    ->setUserId($toUserId)
            );
            $toWallet->refresh();
            $this->logTransaction(
                $toWallet,
                $amount,
                TransactionTypeEnum::DEPOSIT->value,
                $subtype,
                $adminId,
                $depositId,
                null,
                $description ?? 'Funds deposited.',
                $admin_description);

        });
    }


    /**
     * Log a transaction for a wallet.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string $type
     * @param string|null $subtype
     * @param string|null $description
     * @param string|null $admin_description
     * @return void
     */
    private function logTransaction(
        Wallet $wallet,
        float $amount,
        string $type,
        string $subtype,
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
            'subtype' => $subtype,
            'status' => TransactionStatusEnum::SUCCESS,
            'description' => $description,
            'admin_description' => $admin_description
        ]);
    }

}
