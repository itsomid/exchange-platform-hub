<?php

namespace App\Services\Transaction\DTO;

class GetAllDepositWithdrawRequestDTO
{
    private int $userId;

    public function setUserId(int $userId): GetAllDepositWithdrawRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
