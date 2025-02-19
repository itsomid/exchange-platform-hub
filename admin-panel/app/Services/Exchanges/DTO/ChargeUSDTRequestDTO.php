<?php

namespace App\Services\Exchanges\DTO;

class ChargeUSDTRequestDTO
{
    private string $quantity;

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
}
