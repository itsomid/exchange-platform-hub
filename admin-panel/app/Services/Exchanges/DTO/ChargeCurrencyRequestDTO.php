<?php

namespace App\Services\Exchanges\DTO;

class ChargeCurrencyRequestDTO
{
    private string $quantity;

    private string $currency;

    private string $currencyChain;

    private ?string $exchangeSlug = null;

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setCurrencyChain(string $currencyChain): ChargeCurrencyRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }

    public function setCurrency(string $currency): ChargeCurrencyRequestDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setExchangeSlug(?string $exchangeSlug): ChargeCurrencyRequestDTO
    {
        $this->exchangeSlug = $exchangeSlug;

        return $this;
    }

    public function getExchangeSlug(): ?string
    {
        return $this->exchangeSlug;
    }
} 