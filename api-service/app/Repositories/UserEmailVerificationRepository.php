<?php

namespace App\Repositories;

use App\Models\UserEmailVerification;
use App\Repositories\DTO\UserEmailVerification\EmailVerificationSaveTokenRequestDTO;
use App\Repositories\DTO\UserEmailVerification\GetEmailVerificationResponseDTO;
use App\Repositories\Interfaces\UserEmailVerificationInterface;
use Carbon\Carbon;

class UserEmailVerificationRepository implements UserEmailVerificationInterface
{
    public function saveToken(EmailVerificationSaveTokenRequestDTO $DTO): void
    {
        UserEmailVerification::query()
            ->create([
                'user_id' => $DTO->getUserId(),
                'token' => $DTO->getToken(),
                'expiration_date' => $DTO->getExpirationDate(),
            ]);
    }

    public function getByToken(int $token): ?GetEmailVerificationResponseDTO
    {
        $model = UserEmailVerification::query()
            ->where('token', $token)
            ->first();

        if (is_null($model)) {
            return null;
        }

        return resolve(GetEmailVerificationResponseDTO::class)
            ->setUserId($model->user_id)
            ->setToken((int) $model->token)
            ->setExpirationDate(Carbon::parse($model->expiration_date));
    }
}
