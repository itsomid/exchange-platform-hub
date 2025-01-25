<?php

namespace App\Services\OTC\DTO;

class CompletedOrderRequestDTO
{
    private int $otcId;

    private int $buyerUserId;

    private int $sellerUserId;

    public function setOtcId(int $otcId): CompletedOrderRequestDTO
    {
        $this->otcId = $otcId;

        return $this;
    }

    public function getOtcId(): int
    {
        return $this->otcId;
    }

    public function setBuyerUserId(int $buyerUserId): CompletedOrderRequestDTO
    {
        $this->buyerUserId = $buyerUserId;

        return $this;
    }

    public function getBuyerUserId(): int
    {
        return $this->buyerUserId;
    }

    public function setSellerUserId(int $sellerUserId): CompletedOrderRequestDTO
    {
        $this->sellerUserId = $sellerUserId;

        return $this;
    }

    public function getSellerUserId(): int
    {
        return $this->sellerUserId;
    }
}
