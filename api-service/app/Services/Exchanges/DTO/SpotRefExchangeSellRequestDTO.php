<?php

namespace App\Services\Exchanges\DTO;

class SpotRefExchangeSellRequestDTO
{
    private int $spotTradeId;

    private int $marketId;

    private string $quantity;

    public function setMarketId(int $marketId): SpotRefExchangeSellRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): SpotRefExchangeSellRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setSpotTradeId(int $spotTradeId): SpotRefExchangeSellRequestDTO
    {
        $this->spotTradeId = $spotTradeId;

        return $this;
    }

    public function getSpotTradeId(): int
    {
        return $this->spotTradeId;
    }
}
