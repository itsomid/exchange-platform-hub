<?php

namespace App\Infrastructure\HDWalletNew\DTO\Deposit;

class GetDepositListsRequestDTO
{
    private string $userId;
    private ?string $network = null;
    private ?string $currencySymbol = null;
    private int $limit = 50;

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    public function setNetwork(?string $network): self
    {
        $this->network = $network;
        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol(?string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
}
