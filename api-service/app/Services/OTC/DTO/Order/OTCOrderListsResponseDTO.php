<?php

namespace App\Services\OTC\DTO\Order;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use Carbon\Carbon;

class OTCOrderListsResponseDTO
{
    private Carbon $created_at;

    private string $market;

    private OTCOrderTypeEnum $type;

    private string $quantity;

    private string $price;

    private string $fee;

    private OTCOrderStatusEnum $status;

    public function setCreatedAt(Carbon $created_at): OTCOrderListsResponseDTO
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    public function setMarket(string $market): OTCOrderListsResponseDTO
    {
        $this->market = $market;

        return $this;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function setType(OTCOrderTypeEnum $type): OTCOrderListsResponseDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): OTCOrderTypeEnum
    {
        return $this->type;
    }

    public function setQuantity(string $quantity): OTCOrderListsResponseDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setPrice(string $price): OTCOrderListsResponseDTO
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setFee(string $fee): OTCOrderListsResponseDTO
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setStatus(OTCOrderStatusEnum $status): OTCOrderListsResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): OTCOrderStatusEnum
    {
        return $this->status;
    }
}
