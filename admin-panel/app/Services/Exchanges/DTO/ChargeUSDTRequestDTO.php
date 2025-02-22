<?php

namespace App\Services\Exchanges\DTO;

class ChargeUSDTRequestDTO
{
    private string $quantity;

    private string $currency;

    private string $currencyChain;

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setCurrencyChain(string $currencyChain): ChargeUSDTRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }

    public function setCurrency(string $currency): ChargeUSDTRequestDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
