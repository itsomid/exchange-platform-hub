<?php

namespace App\Services\Exchanges\DTO;

class ExchangeSellRequestDTO
{
    private int $otcId;

    private int $marketId;

    private string $quantity;

    public function setMarketId(int $marketId): ExchangeSellRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): ExchangeSellRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setOtcId(int $otcId): ExchangeSellRequestDTO
    {
        $this->otcId = $otcId;

        return $this;
    }

    public function getOtcId(): int
    {
        return $this->otcId;
    }
}
