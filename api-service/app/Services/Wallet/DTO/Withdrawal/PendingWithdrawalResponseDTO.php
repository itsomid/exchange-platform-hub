<?php

namespace App\Services\Wallet\DTO\Withdrawal;

class PendingWithdrawalResponseDTO
{
    private int $withdrawalId;

    private string $currencySymbol;

    private string $blockchain;

    private string $amount;

    private string $address;

    public function setWithdrawalId(int $withdrawalId): PendingWithdrawalResponseDTO
    {
        $this->withdrawalId = $withdrawalId;

        return $this;
    }

    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    public function setCurrencySymbol(string $currencySymbol): PendingWithdrawalResponseDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function setBlockchain(string $blockchain): PendingWithdrawalResponseDTO
    {
        $this->blockchain = $blockchain;

        return $this;
    }

    public function getBlockchain(): string
    {
        return $this->blockchain;
    }

    public function setAmount(string $amount): PendingWithdrawalResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setAddress(string $address): PendingWithdrawalResponseDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
