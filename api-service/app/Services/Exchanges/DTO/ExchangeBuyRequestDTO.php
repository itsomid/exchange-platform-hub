<?php

namespace App\Services\Exchanges\DTO;

class ExchangeBuyRequestDTO
{
    private int $otcId;

    private int $marketId;

    private string $quantity;

    public function setMarketId(int $marketId): ExchangeBuyRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): ExchangeBuyRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setOtcId(int $otcId): ExchangeBuyRequestDTO
    {
        $this->otcId = $otcId;

        return $this;
    }

    public function getOtcId(): int
    {
        return $this->otcId;
    }
}
