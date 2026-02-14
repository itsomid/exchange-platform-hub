<?php

namespace App\Infrastructure\HDWalletNew\DTO\Withdrawal;

class WithdrawResponseDTO
{
    private string $withdrawalId;
    private string $status;
    private string $requestedAmount;
    private string $network;
    private string $currencySymbol;
    private string $toAddress;
    private string $createdAt;
    private ?string $estimatedProcessingTime = null;

    public function getWithdrawalId(): string
    {
        return $this->withdrawalId;
    }

    public function setWithdrawalId(string $withdrawalId): self
    {
        $this->withdrawalId = $withdrawalId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRequestedAmount(): string
    {
        return $this->requestedAmount;
    }

    public function setRequestedAmount(string $requestedAmount): self
    {
        $this->requestedAmount = $requestedAmount;
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

    public function getToAddress(): string
    {
        return $this->toAddress;
    }

    public function setToAddress(string $toAddress): self
    {
        $this->toAddress = $toAddress;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEstimatedProcessingTime(): ?string
    {
        return $this->estimatedProcessingTime;
    }

    public function setEstimatedProcessingTime(?string $estimatedProcessingTime): self
    {
        $this->estimatedProcessingTime = $estimatedProcessingTime;
        return $this;
    }
}
