<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
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

    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {

        try {
            DB::beginTransaction();

            $wallet = Wallet::query()
                ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                ->where('user_id', $requestDTO->getUserId())
            ->lockForUpdate()
            ->first();

            $balance = $wallet->balance;
            if ($requestDTO->getOperation() === BalanceOperationEnum::Increase) {
                $balance = bcadd($wallet->balance, $requestDTO->getAmount(), 8);
            } elseif ($requestDTO->getOperation() === BalanceOperationEnum::Decrease) {
                $balance = bcsub($wallet->balance, $requestDTO->getAmount(), 8);
            }

            Wallet::query()
                ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                ->where('user_id', $requestDTO->getUserId())
                ->update([
                    'balance' => $balance,
                ]);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }

    }
}
