<?php

namespace App\Infrastructure\HDWallet;

use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use Illuminate\Support\Facades\Http;

class Wallet
{
    /**
     * @throws HDDWalletUnavailable
     */
    public function generateAddress(int $userId, string $currencySymbol, string $chain): string
    {
        $currencySymbol = strtolower($currencySymbol);
        $response = Http::post(HDWallet::getBaseUrl()."/api/v1/wallet/{$currencySymbol}", [
            'user_id' => $userId,
            'blockchain' => CurrencyMapEnum::{$chain}->value,
        ]);

        //Already exists
        if ($response->badRequest()) {
            $response = Http::get(HDWallet::getBaseUrl()."/api/v1/wallet/{$currencySymbol}/{$userId}");
            if ($response->serverError()) {
                report($response);
                throw new HDDWalletUnavailable;
            }

            return $response->json('address');
        }

        if ($response->serverError()) {
            report($response);
            throw new HDDWalletUnavailable;
        }

        return $response->json('address');
    }
}
