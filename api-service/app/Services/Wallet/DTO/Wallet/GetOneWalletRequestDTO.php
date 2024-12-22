<?php

namespace App\Services\Wallet\DTO\Wallet;

class GetOneWalletRequestDTO
{
    private int $userId;
    private string $currencySymbol;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }
}
