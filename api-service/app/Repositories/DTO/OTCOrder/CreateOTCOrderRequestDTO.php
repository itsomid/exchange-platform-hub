<?php

namespace App\Repositories\DTO\OTCOrder;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;

class CreateOTCOrderRequestDTO
{
    private int $userId;

    private int $marketId;

    private string $quantity;

    private string $price;

    private string $fee;

    private OTCOrderTypeEnum $type;

    private OTCOrderStatusEnum $status;

    public function setUserId(int $userId): CreateOTCOrderRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setMarketId(int $marketId): CreateOTCOrderRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): CreateOTCOrderRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setPrice(string $price): CreateOTCOrderRequestDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setFee(string $fee): CreateOTCOrderRequestDTO
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setType(OTCOrderTypeEnum $type): CreateOTCOrderRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): OTCOrderTypeEnum
    {
        return $this->type;
    }

    public function setStatus(OTCOrderStatusEnum $status): CreateOTCOrderRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): OTCOrderStatusEnum
    {
        return $this->status;
    }
}
