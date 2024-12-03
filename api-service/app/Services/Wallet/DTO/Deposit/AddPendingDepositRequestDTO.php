<?php

namespace App\Services\Wallet\DTO\Deposit;

use Carbon\Carbon;

class AddPendingDepositRequestDTO
{
    private int $userId;

    private string $currencySymbol;

    private string $currencyChain;

    private string $publicKey;
    private Carbon $expirationDate;

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

    public function setCurrencyChain(string $chainCurrency): self
    {
        $this->currencyChain = $chainCurrency;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setExpirationDate(Carbon $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getExpirationDate(): Carbon
    {
        return $this->expirationDate;
    }
}
