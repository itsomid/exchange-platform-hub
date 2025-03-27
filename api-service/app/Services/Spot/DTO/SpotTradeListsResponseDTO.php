<?php

namespace App\Services\Spot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use Carbon\Carbon;

class SpotTradeListsResponseDTO
{
    private int $id;

    private string $marketName;

    private SpotOrderSideEnum $side;

    private SpotOrderTypeEnum $type;

    private string $quantity;

    private ?string $price = null;

    private SpotOrderStatusEnum $status;

    private string $filledQuantity;

    private string $commission;

    private string $filledValue;

    private Carbon $createdAt;

    public function setMarketName(string $baseCurrency, string $quoteCurrency): SpotTradeListsResponseDTO
    {
        $this->marketName = $baseCurrency.'|'.$quoteCurrency;

        return $this;
    }

    public function getMarketName(): string
    {
        return $this->marketName;
    }

    public function setSide(SpotOrderSideEnum $side): SpotTradeListsResponseDTO
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setType(SpotOrderTypeEnum $type): SpotTradeListsResponseDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setQuantity(string $quantity): SpotTradeListsResponseDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setPrice(?string $price): SpotTradeListsResponseDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setStatus(SpotOrderStatusEnum $status): SpotTradeListsResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): SpotOrderStatusEnum
    {
        return $this->status;
    }

    public function setFilledQuantity(string $filledQuantity): SpotTradeListsResponseDTO
    {
        $this->filledQuantity = $filledQuantity;

        return $this;
    }

    public function getFilledQuantity(): string
    {
        return $this->filledQuantity;
    }

    public function setCommission(string $commission): SpotTradeListsResponseDTO
    {
        $this->commission = $commission;

        return $this;
    }

    public function getCommission(): string
    {
        return $this->commission;
    }

    public function setFilledValue(string $filledValue): SpotTradeListsResponseDTO
    {
        $this->filledValue = $filledValue;

        return $this;
    }

    public function getFilledValue(): string
    {
        return $this->filledValue;
    }

    public function setCreatedAt(Carbon $createdAt): SpotTradeListsResponseDTO
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): SpotTradeListsResponseDTO
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
