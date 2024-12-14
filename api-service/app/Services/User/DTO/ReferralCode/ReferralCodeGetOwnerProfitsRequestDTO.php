<?php

namespace App\Services\User\DTO\ReferralCode;

class ReferralCodeGetOwnerProfitsRequestDTO
{
    private int $referredUserId;

    private int $userId;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setReferredUserId(int $referredUserId): self
    {
        $this->referredUserId = $referredUserId;

        return $this;
    }

    public function getReferredUserId(): int
    {
        return $this->referredUserId;
    }
}
