<?php

namespace App\Repositories\DTO\Deposit;

use App\Enums\DepositStatusEnum;
use Carbon\Carbon;

class CreateDepositRequestDTO
{
    private int $userId;

    private string $currencySymbol;

    private string $currencyChain;

    private ?string $amount = null;

    private string $address;

    private string $transactionHash;

    private Carbon $confirmedAt;

    private ?Carbon $expirationDate = null;

    private DepositStatusEnum $status;

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

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setStatus(DepositStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): DepositStatusEnum
    {
        return $this->status;
    }

    public function setCurrencyChain(string $currencyChain): self
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setExpirationDate(?Carbon $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getExpirationDate(): ?Carbon
    {
        return $this->expirationDate;
    }

    public function setTransactionHash(string $transactionHash): CreateDepositRequestDTO
    {
        $this->transactionHash = $transactionHash;

        return $this;
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    public function setConfirmedAt(Carbon $confirmedAt): CreateDepositRequestDTO
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getConfirmedAt(): Carbon
    {
        return $this->confirmedAt;
    }
}
