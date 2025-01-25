<?php

namespace App\Services\Exchanges\Asset\DTO;

use Carbon\Carbon;

class BuyDTOResponse
{
    private bool $isDone;

    private int $orderId;

    private string $market;

    private string $side;

    private string $amount;

    private string $price;

    private string $unfilledAmount;

    private string $filledAmount;

    private string $filledValue;

    private string $makerFeeRate;

    private string $takerFeeRate;

    private string $lastFillAmount;

    private string $lastFillPrice;

    private string $baseFee;

    private string $quoteFee;

    private string $discountFee;

    private Carbon $createdAt;

    private string $responseBody;

    public function setMarket(string $market): BuyDTOResponse
    {
        $this->market = $market;

        return $this;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function setSide(string $side): BuyDTOResponse
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function setOrderId(int $orderId): BuyDTOResponse
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setAmount(string $amount): BuyDTOResponse
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setPrice(string $price): BuyDTOResponse
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setUnfilledAmount(string $unfilledAmount): BuyDTOResponse
    {
        $this->unfilledAmount = $unfilledAmount;

        return $this;
    }

    public function getUnfilledAmount(): string
    {
        return $this->unfilledAmount;
    }

    public function setFilledAmount(string $filledAmount): BuyDTOResponse
    {
        $this->filledAmount = $filledAmount;

        return $this;
    }

    public function getFilledAmount(): string
    {
        return $this->filledAmount;
    }

    public function setFilledValue(string $filledValue): BuyDTOResponse
    {
        $this->filledValue = $filledValue;

        return $this;
    }

    public function getFilledValue(): string
    {
        return $this->filledValue;
    }

    public function setMakerFeeRate(string $makerFeeRate): BuyDTOResponse
    {
        $this->makerFeeRate = $makerFeeRate;

        return $this;
    }

    public function getMakerFeeRate(): string
    {
        return $this->makerFeeRate;
    }

    public function setTakerFeeRate(string $takerFeeRate): BuyDTOResponse
    {
        $this->takerFeeRate = $takerFeeRate;

        return $this;
    }

    public function getTakerFeeRate(): string
    {
        return $this->takerFeeRate;
    }

    public function setLastFillAmount(string $lastFillAmount): BuyDTOResponse
    {
        $this->lastFillAmount = $lastFillAmount;

        return $this;
    }

    public function getLastFillAmount(): string
    {
        return $this->lastFillAmount;
    }

    public function setLastFillPrice(string $lastFillPrice): BuyDTOResponse
    {
        $this->lastFillPrice = $lastFillPrice;

        return $this;
    }

    public function getLastFillPrice(): string
    {
        return $this->lastFillPrice;
    }

    public function setBaseFee(string $baseFee): BuyDTOResponse
    {
        $this->baseFee = $baseFee;

        return $this;
    }

    public function getBaseFee(): string
    {
        return $this->baseFee;
    }

    public function setQuoteFee(string $quoteFee): BuyDTOResponse
    {
        $this->quoteFee = $quoteFee;

        return $this;
    }

    public function getQuoteFee(): string
    {
        return $this->quoteFee;
    }

    public function setCreatedAt(Carbon $createdAt): BuyDTOResponse
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setDiscountFee(string $discountFee): BuyDTOResponse
    {
        $this->discountFee = $discountFee;

        return $this;
    }

    public function getDiscountFee(): string
    {
        return $this->discountFee;
    }

    public function setResponseBody(string $responseBody): BuyDTOResponse
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function setIsDone(bool $isDone): BuyDTOResponse
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }
}
