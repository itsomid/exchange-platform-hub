<?php

namespace App\Services\Exchanges\Asset\DTO;

class BuyDTORequest
{
    private string $market;

    private string $marketType;

    private string $orderType;

    private string $side;

    private string $price;

    private string $quantity;

    private string $currency;

    private ?string $tradeType = null;

    private ?int $userId = null;

    private ?int $orderId = null;

    public function setMarket(string $market): BuyDTORequest
    {
        $this->market = $market;

        return $this;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function setMarketType(string $marketType): BuyDTORequest
    {
        $this->marketType = $marketType;

        return $this;
    }

    public function getMarketType(): string
    {
        return $this->marketType;
    }

    public function setSide(string $side): BuyDTORequest
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function setPrice(string $price): BuyDTORequest
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setQuantity(string $quantity): BuyDTORequest
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setOrderType(string $orderType): BuyDTORequest
    {
        $this->orderType = $orderType;

        return $this;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function setCurrency(string $currency): BuyDTORequest
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setTradeType(string $tradeType): BuyDTORequest
    {
        $this->tradeType = $tradeType;

        return $this;
    }

    public function getTradeType(): ?string
    {
        return $this->tradeType;
    }

    public function setUserId(?int $userId): BuyDTORequest
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setOrderId(?int $orderId): BuyDTORequest
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }
}
