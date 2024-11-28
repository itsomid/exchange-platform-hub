<?php

namespace App\Services\User\DTO\ReferralCode;

class ReferralCodeCreateRequestDTO
{
    private int $userId;

    private int $friendFee;

    private int $maxFee;

    private int $usageLimit;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setFriendFee(int $friendFee): self
    {
        $this->friendFee = $friendFee;

        return $this;
    }

    public function getFriendFee(): int
    {
        return $this->friendFee;
    }

    public function setUsageLimit(int $usageLimit): self
    {
        $this->usageLimit = $usageLimit;

        return $this;
    }

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }

    public function setMaxFee(int $maxFee): self
    {
        $this->maxFee = $maxFee;

        return $this;
    }

    public function getMaxFee(): int
    {
        return $this->maxFee;
    }
}
