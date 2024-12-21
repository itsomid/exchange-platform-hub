<?php

namespace App\Repositories\Interfaces;

use App\Enums\EmailOTPActionEnum;
use App\Models\EmailOTP;
use App\Repositories\DTO\System\EmailOTP\SaveNewEmailRequestDTO;

interface EmailOTPRepositoryInterface
{
    public function saveNewEmail(SaveNewEmailRequestDTO $requestDTO): void;

    public function getLastToken(string $email, EmailOTPActionEnum $action): ?EmailOTP;
}
