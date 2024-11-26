<?php

namespace App\Repositories\DTO\ReferralCode;

class ReferralCodeCreateDTO
{
    private string $code;

    private int $userId;

    private int $introducerFee;

    private int $friendFee;

    private int $usageLimit;

    public function setCode(string $code): ReferralCodeCreateDTO
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setUserId(int $userId): ReferralCodeCreateDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setIntroducerFee(int $introducerFee): ReferralCodeCreateDTO
    {
        $this->introducerFee = $introducerFee;

        return $this;
    }

    public function getIntroducerFee(): int
    {
        return $this->introducerFee;
    }

    public function setFriendFee(int $friendFee): ReferralCodeCreateDTO
    {
        $this->friendFee = $friendFee;

        return $this;
    }

    public function getFriendFee(): int
    {
        return $this->friendFee;
    }

    public function setUsageLimit(int $usageLimit): ReferralCodeCreateDTO
    {
        $this->usageLimit = $usageLimit;

        return $this;
    }

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }
}
