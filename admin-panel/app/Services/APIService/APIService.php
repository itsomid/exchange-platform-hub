<?php

namespace App\Services\APIService;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

class APIService
{
    public function checkWithdrawal(int $userId): void
    {
        $user = User::query()->find($userId);
        $token = $user->generateAccessToken();

        try {
            Http::withHeader('Authorization', 'Bearer '.$token)
                ->post(config('bitexroom.api_service.base_url').'/api/v1/wallets/check-withdrawal');
        } catch (Throwable $exception) {

        }

    }
}
