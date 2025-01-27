<?php

namespace App\Services\OTC\DTO;

class MarketResponseDTO
{
    private int $marketId;

    private string $baseCurrency;

    private string $quoteCurrency;

    private string $buyPrice;

    private string $sellPrice;

    private bool $isActive;

    private string $minTradeAmount;

    private string $maxTradeAmount;

    private string $minOTCAmount;

    private string $maxOTCAmount;

    public function setBaseCurrency(string $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function setQuoteCurrency(string $quoteCurrency): self
    {
        $this->quoteCurrency = $quoteCurrency;

        return $this;
    }

    public function getQuoteCurrency(): string
    {
        return $this->quoteCurrency;
    }

    public function setBuyPrice(string $buyPrice): self
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    public function getBuyPrice(): string
    {
        return $this->buyPrice;
    }

    public function setSellPrice(string $sellPrice): self
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getSellPrice(): string
    {
        return $this->sellPrice;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setMinTradeAmount(string $minTradeAmount): self
    {
        $this->minTradeAmount = $minTradeAmount;

        return $this;
    }

    public function getMinTradeAmount(): string
    {
        return $this->minTradeAmount;
    }

    public function setMaxTradeAmount(string $maxTradeAmount): self
    {
        $this->maxTradeAmount = $maxTradeAmount;

        return $this;
    }

    public function getMaxTradeAmount(): string
    {
        return $this->maxTradeAmount;
    }

    public function setMarketId(int $marketId): self
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setMinOTCAmount(string $minOTCAmount): MarketResponseDTO
    {
        $this->minOTCAmount = $minOTCAmount;

        return $this;
    }

    public function getMinOTCAmount(): string
    {
        return $this->minOTCAmount;
    }

    public function setMaxOTCAmount(string $maxOTCAmount): MarketResponseDTO
    {
        $this->maxOTCAmount = $maxOTCAmount;

        return $this;
    }

    public function getMaxOTCAmount(): string
    {
        return $this->maxOTCAmount;
    }
}
