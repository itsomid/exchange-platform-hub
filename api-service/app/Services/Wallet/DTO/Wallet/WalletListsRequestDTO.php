<?php

namespace App\Services\Wallet\DTO\Wallet;

class WalletListsRequestDTO
{
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
}
