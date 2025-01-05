<?php

namespace App\Services\Wallet\DTO\CheckWallet;

class CheckUserDepositRequestDTO
{
    private int $userId;

    private string $currencySymbol;

    private string $currencyChain;

    public function setUserId(int $userId): CheckUserDepositRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setCurrencySymbol(string $currencySymbol): CheckUserDepositRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setCurrencyChain(string $currencyChain): CheckUserDepositRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }
}
