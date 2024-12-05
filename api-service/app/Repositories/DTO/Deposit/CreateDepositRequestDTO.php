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

    private string $publicKey;

    private Carbon $expirationDate;

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
