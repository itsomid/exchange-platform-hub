<?php

namespace App\Services\Auth\DTO;

class DisableTwoFactorRequestDTO
{
    private int $userId;

    private string $google2fa;

    public function setUserId(int $userId): DisableTwoFactorRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setGoogle2fa(string $google2fa): DisableTwoFactorRequestDTO
    {
        $this->google2fa = $google2fa;

        return $this;
    }

    public function getGoogle2fa(): string
    {
        return $this->google2fa;
    }
}
