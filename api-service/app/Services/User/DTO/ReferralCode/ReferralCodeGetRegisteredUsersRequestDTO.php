<?php

namespace App\Services\User\DTO\ReferralCode;

class ReferralCodeGetRegisteredUsersRequestDTO
{
    private int $userId;

    private string $referralCode;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setReferralCode(string $referralCode): self
    {
        $this->referralCode = $referralCode;

        return $this;
    }

    public function getReferralCode(): string
    {
        return $this->referralCode;
    }
}
