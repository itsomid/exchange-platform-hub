<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    /**
     * Calculate the total assets value for a user's wallets.
     *
     * @param  \App\Models\User  $user
     * @return float
     */
    public function totalAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            // Get the current market price for the wallet's currency
            $market = $wallet->currency->baseMarkets->first(); // Assuming you have a relationship in the Currency model

            $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

            $totalAssetsValue += $wallet->balance * $currencyPrice;
//
        }

        return $totalAssetsValue;
    }

    public function specificAssetValue(User $user, string $currency_symbol)
    {
        // Initialize the specific asset value
        $specificAssetValue = 0;

        // Loop through the user's wallets
        foreach ($user->wallets as $wallet) {
            // Check if the wallet's currency matches the provided currency ID
            if ($wallet->currency_symbol === $currency_symbol) {
                // Get the current market price for the wallet's currency
                $market = $wallet->currency->baseMarkets->first(); // Assuming you have a relationship in the Currency model

                $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

                // Add the wallet's value to the specific asset value
                $specificAssetValue += $wallet->balance * $currencyPrice;
            }
        }

        return $specificAssetValue;
    }
    public function totalTransactionValueBasedType(string $currencySymbol, array $transactionTypes): float
    {
        // Get the total amount of deposits for the given currency
        $totalDeposits = Transaction::whereIn('type', $transactionTypes)
            ->whereHas('wallet', function ($query) use ($currencySymbol) {
                $query->where('currency_symbol', $currencySymbol);
            })
            ->sum('amount');

        // Fetch the exchange rate for the currency
        $currency = Currency::where('symbol', $currencySymbol)->first();
        $exchangeRate = $currency && $currency->baseMarkets->first()
            ? $currency->baseMarkets->first()->activeExchangePrice->price
            : 1; // Default to 1 if no exchange rate found

        // Calculate the value in USDT
        return $totalDeposits * $exchangeRate;
    }
    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {
        try {
            DB::transaction(function () use ($requestDTO) {
                $wallet = Wallet::query()
                    ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                    ->where('user_id', $requestDTO->getUserId())
                    ->lockForUpdate()
                    ->firstOrFail(); // Ensures wallet exists, throws exception otherwise

                $newBalance = $this->calculateNewBalance(
                    $wallet->balance,
                    $requestDTO->getAmount(),
                    $requestDTO->getOperation()
                );

                $wallet->update(['balance' => $newBalance]);
            });

            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }
    private function calculateNewBalance(string $currentBalance, string $amount, BalanceOperationEnum $operation): string
    {
        return match ($operation) {
            BalanceOperationEnum::INCREASE => bcadd($currentBalance, $amount, 8),
            BalanceOperationEnum::DECREASE => bcsub($currentBalance, $amount, 8),
            default => throw new \InvalidArgumentException("Invalid balance operation: {$operation}"),
        };
    }
}
