<?php

namespace App\Services\Auth\DTO;

class CheckTwoFactorRequestDTO
{
    private string $google2fa;

    private int $userId;

    public function setGoogle2fa(string $google2fa): self
    {
        $this->google2fa = $google2fa;

        return $this;
    }

    public function getGoogle2fa(): string
    {
        return $this->google2fa;
    }

    public function setUserId(int $userId): CheckTwoFactorRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
