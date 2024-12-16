<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    /**
     * Calculate the total assets value for a user's wallets.
     *
     * @param  \App\Models\User  $user
     * @return float
     */
    public function totalAssets(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            // Get the current market price for the wallet's currency
            $market = $wallet->currency->baseMarkets->first(); // Assuming you have a relationship in the Currency model

            $currencyPrice = $market ? $market->activeExchangePrices->price : 1;

            $totalAssetsValue += $wallet->balance * $currencyPrice;
//
        }

        return $totalAssetsValue;
    }
}
