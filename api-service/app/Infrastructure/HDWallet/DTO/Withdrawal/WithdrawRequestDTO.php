<?php

namespace App\Infrastructure\HDWallet\DTO\Withdrawal;

class WithdrawRequestDTO
{
    private string $currencySymbol;

    private string $blockchain;

    private int $withdrawalId;

    private int $userId;

    private string $amount;

    private string $withdrawAddress;

    public function setCurrencySymbol(string $currencySymbol): WithdrawRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setBlockchain(string $blockchain): WithdrawRequestDTO
    {
        $this->blockchain = $blockchain;

        return $this;
    }

    public function getBlockchain(): string
    {
        return $this->blockchain;
    }

    public function setWithdrawalId(int $withdrawalId): WithdrawRequestDTO
    {
        $this->withdrawalId = $withdrawalId;

        return $this;
    }

    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    public function setUserId(int $userId): WithdrawRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setAmount(string $amount): WithdrawRequestDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setWithdrawAddress(string $withdrawAddress): WithdrawRequestDTO
    {
        $this->withdrawAddress = $withdrawAddress;

        return $this;
    }

    public function getWithdrawAddress(): string
    {
        return $this->withdrawAddress;
    }
}
