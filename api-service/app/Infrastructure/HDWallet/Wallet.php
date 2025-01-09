<?php

namespace App\Infrastructure\HDWallet;

use App\Infrastructure\HDWallet\Exceptions\HDDWalletServerError;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Wallet
{
    /**
     * @throws HDDWalletUnavailable
     */
    public function generateAddress(int $userId, string $blockchainName): string
    {
        try {
            $response = Http::post(HDWallet::getBaseUrl()."/api/v1/wallet/{$blockchainName}", [
                'user_id' => $userId,
                'blockchain' => $blockchainName,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }

        //Already exists
        if ($response->badRequest()) {
            $response = Http::get(HDWallet::getBaseUrl()."/api/v1/wallet/{$blockchainName}/{$userId}");
            return $response->json();
        }

        if (! $response->ok() || ! $response->created()) {
            report($response->body());
            throw new HDDWalletServerError;
        }

        return $response->json('address');
    }
}
