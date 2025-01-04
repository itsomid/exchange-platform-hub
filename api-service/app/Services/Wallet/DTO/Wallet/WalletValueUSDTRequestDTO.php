<?php

namespace App\Services\Wallet\DTO\Wallet;

class WalletValueUSDTRequestDTO
{
    private int $userId;

    public function setUserId(int $userId): WalletValueUSDTRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
