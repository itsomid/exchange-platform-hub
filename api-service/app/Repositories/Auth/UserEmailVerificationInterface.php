<?php

namespace App\Repositories\Auth;

interface UserEmailVerificationInterface
{
    public function saveToken(EmailVerificationSaveTokenRequestDTO $DTO): void;

    public function getByToken(int $token): ?GetEmailVerificationResponseDTO;
}
