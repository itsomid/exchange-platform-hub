<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\UserEmailVerification\EmailVerificationSaveTokenRequestDTO;
use App\Repositories\DTO\UserEmailVerification\GetEmailVerificationResponseDTO;

interface UserEmailVerificationInterface
{
    public function saveToken(EmailVerificationSaveTokenRequestDTO $DTO): void;

    public function getByToken(int $token): ?GetEmailVerificationResponseDTO;
}
