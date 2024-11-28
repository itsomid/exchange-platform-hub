<?php

namespace App\Services\Auth\DTO;

class TwoFactorSaveSecretRequestDTO
{
    private int $userId;

    private string $google2fa;

    private string $google2faSecret;

    public function setGoogle2fa(string $google2fa): self
    {
        $this->google2fa = $google2fa;

        return $this;
    }

    public function getGoogle2fa(): string
    {
        return $this->google2fa;
    }

    public function setGoogle2faSecret(string $google2faSecret): self
    {
        $this->google2faSecret = $google2faSecret;

        return $this;
    }

    public function getGoogle2faSecret(): string
    {
        return $this->google2faSecret;
    }

    public function setUserId(int $userId): TwoFactorSaveSecretRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
