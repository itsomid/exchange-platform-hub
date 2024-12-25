<?php

namespace App\Services\OTC\DTO;

class BuyRequestDTO
{
    private int $buyerUserId;

    private int $sellerUserId;

    private string $marketId;

    private string $quantity;

    public function setBuyerUserId(int $buyerUserId): BuyRequestDTO
    {
        $this->buyerUserId = $buyerUserId;

        return $this;
    }

    public function getBuyerUserId(): int
    {
        return $this->buyerUserId;
    }

    public function setSellerUserId(int $sellerUserId): BuyRequestDTO
    {
        $this->sellerUserId = $sellerUserId;

        return $this;
    }

    public function getSellerUserId(): int
    {
        return $this->sellerUserId;
    }

    public function setMarketId(string $marketId): BuyRequestDTO
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): string
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): BuyRequestDTO
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }
}
