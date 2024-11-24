<?php

namespace App\Repositories\Auth;

use Carbon\Carbon;

class EmailVerificationSaveTokenRequestDTO
{
    private int $token;

    private int $userId;

    private Carbon $expirationDate;

    public function setToken(int $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): int
    {
        return $this->token;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setExpirationDate(Carbon $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getExpirationDate(): Carbon
    {
        return $this->expirationDate;
    }
}
