<?php

namespace App\Infrastructure\HDWalletNew\DTO\Address;

class GenerateAddressRequestDTO
{
    private int $userId;
    private string $network;
    private string $currencySymbol;

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
}
