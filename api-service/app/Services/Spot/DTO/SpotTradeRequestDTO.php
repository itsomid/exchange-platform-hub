<?php

namespace App\Services\Spot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;

class SpotTradeRequestDTO
{
    private int $userId;

    private int $marketId;

    private SpotOrderTypeEnum $type;

    private SpotOrderSideEnum $side;

    private string $quantity;

    private string $price;

    public function setUserId(int $userId): SpotTradeRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setMarketId(int $marketId): SpotTradeRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setType(SpotOrderTypeEnum $type): SpotTradeRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setSide(SpotOrderSideEnum $side): SpotTradeRequestDTO
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setQuantity(string $quantity): SpotTradeRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setPrice(string $price): SpotTradeRequestDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }
}
