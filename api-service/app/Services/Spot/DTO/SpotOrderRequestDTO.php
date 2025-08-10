<?php

namespace App\Services\Spot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderSourceEnum;

class SpotOrderRequestDTO
{
    private int $userId;

    private int $marketId;

    private SpotOrderTypeEnum $type;

    private SpotOrderSideEnum $side;

    private string $quantity;

    private ?string $price = null;

    private ?SpotOrderSourceEnum $source = null;

    public function setSource(SpotOrderSourceEnum $source): SpotOrderRequestDTO
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): SpotOrderSourceEnum
    {
        return $this->source;
    }

    public function setUserId(int $userId): SpotOrderRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setMarketId(int $marketId): SpotOrderRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setType(SpotOrderTypeEnum $type): SpotOrderRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setSide(SpotOrderSideEnum $side): SpotOrderRequestDTO
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setQuantity(string $quantity): SpotOrderRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setPrice(?string $price): SpotOrderRequestDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }
}
