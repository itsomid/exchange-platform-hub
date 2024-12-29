<?php

namespace App\Services\Transaction;

use App\Enums\BalanceOperationEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\Wallet;
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
        string  $type,
        ?string $subtype = null,
        ?int    $adminId = null,
        ?string $description = null,
        ?string $admin_description = null
    ): void
    {
        \DB::transaction(function () use ($fromUserId, $toUserId, $amount, $currency, $type, $subtype, $adminId, $description, $admin_description) {
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
                throw new \Exception('موجودی ناکافی کیف پول مبدا برای برداشت.');
            }

            // Update balances using WalletService
            $this->walletService->updateBalance(
                resolve(UpdateBalanceRequestDTO::class)
                    ->setAmount($amount)
                    ->setOperation(BalanceOperationEnum::DECREASE)
                    ->setCurrencySymbol($currency)
                    ->setUserId($fromUserId)
            );
            $fromWallet->refresh();
            $this->logTransaction($fromWallet, -$amount, 'withdrawal', $subtype, $adminId, $description ?? 'Funds withdrawn.', $admin_description);

            $this->walletService->updateBalance(
                resolve(UpdateBalanceRequestDTO::class)
                    ->setAmount($amount)
                    ->setOperation(BalanceOperationEnum::INCREASE)
                    ->setCurrencySymbol($currency)
                    ->setUserId($toUserId)
            );
            $toWallet->refresh();
            $this->logTransaction($toWallet, $amount, 'deposit', $subtype, $adminId, $description ?? 'Funds deposited.', $admin_description);
            // Update balances


        });
    }


    public function buyCoin(
        int     $userId,
        float   $usdtAmount,
        string  $toCurrency,
        float   $exchangeRate,
        float   $feeRate = 0,
        ?string $context = 'buy',
        ?string $description = null
    ): void
    {
        \DB::transaction(function () use ($userId, $usdtAmount, $toCurrency, $exchangeRate, $feeRate, $context, $description) {
            // Calculate BTC amount and fee
            $btcAmount = $usdtAmount / $exchangeRate;
            $fee = $btcAmount * $feeRate;
            $btcAmountAfterFee = $btcAmount - $fee;

            // Fetch wallets
            $userUsdtWallet = Wallet::firstOrCreate(
                ['user_id' => $userId, 'currency_symbol' => 'USDT'],
                ['balance' => 0]
            );

            $userBtcWallet = Wallet::firstOrCreate(
                ['user_id' => $userId, 'currency_symbol' => $toCurrency],
                ['balance' => 0]
            );

            $exchangeUsdtWallet = Wallet::firstOrCreate(
                ['user_id' => self::EXCHANGE_USER_ID, 'currency_symbol' => 'USDT'],
                ['balance' => 0]
            );

            $exchangeBtcWallet = Wallet::firstOrCreate(
                ['user_id' => self::EXCHANGE_USER_ID, 'currency_symbol' => $toCurrency],
                ['balance' => 0]
            );

            // Validate sufficient USDT in user's wallet
            if ($userUsdtWallet->balance < $usdtAmount) {
                throw new \Exception('Insufficient USDT balance in user wallet.');
            }

            // Validate sufficient BTC in exchange wallet
            if ($exchangeBtcWallet->balance < $btcAmountAfterFee) {
                throw new \Exception('Insufficient BTC balance in exchange wallet.');
            }

            // Step 1: Deduct USDT from user and credit to exchange
            $this->walletService->updateBalance($userUsdtWallet, -$usdtAmount);
            $this->logTransaction($userUsdtWallet, -$usdtAmount, 'withdraw', $context, $description ?? 'USDT withdrawn for BTC purchase.');

            $this->walletService->updateBalance($exchangeUsdtWallet, $usdtAmount);
            $this->logTransaction($exchangeUsdtWallet, $usdtAmount, 'deposit', $context, $description ?? 'USDT received from user.');

            // Step 2: Deduct BTC from exchange and credit to user
            $this->walletService->updateBalance($exchangeBtcWallet, -$btcAmountAfterFee);
            $this->logTransaction($exchangeBtcWallet, -$btcAmountAfterFee, 'withdraw', $context, $description ?? 'BTC sent to user.');

            $this->walletService->updateBalance($userBtcWallet, $btcAmountAfterFee);
            $this->logTransaction($userBtcWallet, $btcAmountAfterFee, 'deposit', $context, $description ?? 'BTC received from exchange.');

            // Step 3: Optionally log fee (if applicable)
            if ($fee > 0) {
                $this->logTransaction($exchangeBtcWallet, $fee, 'fee', $context, 'Fee collected for BTC purchase.');
            }
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
     * @return Transaction
     */
    private function logTransaction(Wallet $wallet, float $amount, string $type, ?string $subtype = null, ?int $adminId, ?string $description = null, ?string $admin_description = null): Transaction
    {
        return Transaction::create([
            'user_id' => $wallet->user_id,
            'admin_id' => $adminId,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance' => $wallet->balance,
            'type' => $type,
            'subtype' => $subtype,
            'status' => 'completed',
            'description' => $description,
            'admin_description' => $admin_description
        ]);
    }

}
