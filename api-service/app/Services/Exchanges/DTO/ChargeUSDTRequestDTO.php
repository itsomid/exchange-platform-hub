<?php

namespace App\Services\Exchanges\DTO;

class ChargeUSDTRequestDTO
{
    private int $otcId;

    private int $marketId;

    private string $quantity;

    private string $currencyChain;

    public function setMarketId(int $marketId): self
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setOtcId(int $otcId): self
    {
        $this->otcId = $otcId;

        return $this;
    }

    public function getOtcId(): int
    {
        return $this->otcId;
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
