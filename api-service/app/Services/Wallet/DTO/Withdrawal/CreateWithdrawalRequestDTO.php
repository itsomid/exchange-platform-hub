<?php

namespace App\Services\Wallet\DTO\Withdrawal;

class CreateWithdrawalRequestDTO
{
    private int $userId;

    private string $currencyChain;

    private string $currencySymbol;

    private string $amount;

    private string $address;

    public function setUserId(int $userId): CreateWithdrawalRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setAmount(string $amount): CreateWithdrawalRequestDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function setAddress(string $address): CreateWithdrawalRequestDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setCurrencySymbol(string $currencySymbol): CreateWithdrawalRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setCurrencyChain(string $currencyChain): CreateWithdrawalRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }
}
