<?php

namespace App\Repositories;

use App\Enums\EmailOTPActionEnum;
use App\Models\EmailOTP;
use App\Repositories\DTO\System\EmailOTP\SaveNewEmailRequestDTO;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;

class EmailOTPRepository implements EmailOTPRepositoryInterface
{
    public function saveNewEmail(SaveNewEmailRequestDTO $requestDTO): void
    {
        EmailOTP::query()
            ->create([
                'email' => $requestDTO->getEmail(),
                'code' => $requestDTO->getCode(),
                'action' => $requestDTO->getAction(),
            ]);
    }

    public function getLastToken(string $email, EmailOTPActionEnum $action): ?EmailOTP
    {
        return EmailOTP::query()
            ->where('email', $email)
            ->where('action', $action)
            ->latest()
            ->first();
    }

    public function deleteOldTokens(string $email, EmailOTPActionEnum $action): void
    {
        EmailOTP::query()
            ->where('email', $email)
            ->where('action', $action)
            ->delete();
    }
}
