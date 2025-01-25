<?php

namespace App\Services\OTC\DTO;

class OTCSellRequestDTO
{
    private int $buyerUserId;

    private int $sellerUserId;

    private int $marketId;

    private string $quantity;

    public function setBuyerUserId(int $buyerUserId): self
    {
        $this->buyerUserId = $buyerUserId;

        return $this;
    }

    public function getBuyerUserId(): int
    {
        return $this->buyerUserId;
    }

    public function setSellerUserId(int $sellerUserId): self
    {
        $this->sellerUserId = $sellerUserId;

        return $this;
    }

    public function getSellerUserId(): int
    {
        return $this->sellerUserId;
    }

    public function setMarketId(int $marketId): self
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): int
    {
        return $this->marketId;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }
}
