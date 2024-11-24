<?php

namespace App\Services\Auth\DTO;

class EmailVerifyRequestDTO
{
    private int $userId;

    private int $token;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setToken(int $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): int
    {
        return $this->token;
    }
}
