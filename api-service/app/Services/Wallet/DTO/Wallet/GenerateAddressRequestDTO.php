<?php

namespace App\Services\Wallet\DTO\Wallet;

class GenerateAddressRequestDTO
{
    private int $userId;

    private string $currency;

    private string $chainSymbol;

    public function setUserId(int $userId): GenerateAddressRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setCurrency(string $currency): GenerateAddressRequestDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setChainSymbol(string $chainSymbol): GenerateAddressRequestDTO
    {
        $this->chainSymbol = $chainSymbol;

        return $this;
    }

    public function getChainSymbol(): string
    {
        return $this->chainSymbol;
    }
}
