<?php

namespace App\Repositories\DTO\SpotOrder;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;

class SpotOrderCreateRequestDTO
{
    private int $userId;

    private int $marketId;

    private string $quantity;

    private SpotOrderSideEnum $side;

    private SpotOrderTypeEnum $type;

    private ?string $price = null;

    private SpotOrderStatusEnum $status;

    private int $filledQuantity;

    public function setUserId(int $userId): SpotOrderCreateRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setMarketId(int $marketId): SpotOrderCreateRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setSide(SpotOrderSideEnum $side): SpotOrderCreateRequestDTO
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setType(SpotOrderTypeEnum $type): SpotOrderCreateRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setPrice(?string $price): SpotOrderCreateRequestDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setStatus(SpotOrderStatusEnum $status): SpotOrderCreateRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): SpotOrderStatusEnum
    {
        return $this->status;
    }

    public function setFilledQuantity(int $filledQuantity): SpotOrderCreateRequestDTO
    {
        $this->filledQuantity = $filledQuantity;

        return $this;
    }

    public function getFilledQuantity(): int
    {
        return $this->filledQuantity;
    }

    public function setQuantity(string $quantity): SpotOrderCreateRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }
}
