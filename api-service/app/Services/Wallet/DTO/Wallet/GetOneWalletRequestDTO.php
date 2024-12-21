<?php

namespace App\Services\Wallet\DTO\Wallet;

class GetOneWalletRequestDTO
{
    private int $userId;
    private string $symbol;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }
}
