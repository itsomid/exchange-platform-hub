<?php

namespace App\Infrastructure\HDWalletNew\DTO\Withdrawal;

class WithdrawRequestDTO
{
    private int $withdrawalId;
    private int $userId;
    private string $network;
    private string $currencySymbol;
    private string $amount;
    private string $toAddress;
    private ?string $tag = null;
    private string $priority = 'normal';

    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    public function setWithdrawalId(int $withdrawalId): self
    {
        $this->withdrawalId = $withdrawalId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): self
    {
        $this->network = $network;
        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getToAddress(): string
    {
        return $this->toAddress;
    }

    public function setToAddress(string $toAddress): self
    {
        $this->toAddress = $toAddress;
        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
}
