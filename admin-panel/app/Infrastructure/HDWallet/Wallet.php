<?php

namespace App\Infrastructure\HDWallet;

use App\Functions\FlashMessages\Toast;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Wallet
{

    public function generateAddress(int $userId, string $blockchainName): string
    {
        try {
            $response = Http::post(HDWallet::getBaseUrl() . "/api/v1/wallet/{$blockchainName}", [
                'user_id' => $userId,
                'blockchain' => $blockchainName,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);
            Toast::message('سرویس کیف پول موقتاً در دسترس نیست. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return null;
        }

        //Already exists
        if ($response->badRequest()) {
            $response = Http::get(HDWallet::getBaseUrl() . "/api/v1/wallet/{$blockchainName}/{$userId}");
        }

        if (! ($response->ok() || $response->created())) {
            report($response->body());
            Toast::message('خطا در سرویس کیف پول. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return null;
        }

        return $response->json('address');
    }
}
