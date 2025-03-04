<?php

namespace App\Services\Exchanges\Asset\DTO;

use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use Carbon\Carbon;

class WithdrawResponseDTO
{
    private int $withdrawId;

    private Carbon $createdAt;

    private string $currency;

    private string $exchange;

    private string $chain;

    private string $amount;

    private string $actualAmount;

    private string $fee;

    private ?string $currencyFee = null;

    private WithdrawMethodEnum $withdrawMethod;

    private string $address;

    private int $confirmationCount;

    private string $exploreAddress;

    private WithdrawStatusEnum $status;

    public function setWithdrawId(int $withdrawId): WithdrawResponseDTO
    {
        $this->withdrawId = $withdrawId;

        return $this;
    }

    public function getWithdrawId(): int
    {
        return $this->withdrawId;
    }

    public function setExchange(string $exchange): WithdrawResponseDTO
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function getExchange()
    {
        return $this->exchange;
    }

    public function setCreatedAt(int $createdAt): WithdrawResponseDTO
    {
        $this->createdAt = Carbon::createFromTimestampMs($createdAt);

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCurrency(string $currency): WithdrawResponseDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setChain(string $chain): WithdrawResponseDTO
    {
        $this->chain = $chain;

        return $this;
    }

    public function getChain(): string
    {
        return $this->chain;
    }

    public function setAmount(string $amount): WithdrawResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setActualAmount(string $actualAmount): WithdrawResponseDTO
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    public function getActualAmount(): string
    {
        return $this->actualAmount;
    }

    public function setWithdrawMethod(string $withdrawMethod): WithdrawResponseDTO
    {
        $this->withdrawMethod = WithdrawMethodEnum::from(strtolower($withdrawMethod));

        return $this;
    }

    public function getWithdrawMethod(): WithdrawMethodEnum
    {
        return $this->withdrawMethod;
    }

    public function setAddress(string $address): WithdrawResponseDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setConfirmationCount(int $confirmationCount): WithdrawResponseDTO
    {
        $this->confirmationCount = $confirmationCount;

        return $this;
    }

    public function getConfirmationCount(): int
    {
        return $this->confirmationCount;
    }

    public function setExploreAddress(string $exploreAddress): WithdrawResponseDTO
    {
        $this->exploreAddress = $exploreAddress;

        return $this;
    }

    public function getExploreAddress(): string
    {
        return $this->exploreAddress;
    }

    public function setStatus(string $status): WithdrawResponseDTO
    {
        $this->status = WithdrawStatusEnum::from($status);

        return $this;
    }

    public function getStatus(): WithdrawStatusEnum
    {
        return $this->status;
    }

    public function setFee(string $fee): WithdrawResponseDTO
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setCurrencyFee(?string $currencyFee): WithdrawResponseDTO
    {
        $this->currencyFee = $currencyFee;

        return $this;
    }

    public function getCurrencyFee(): ?string
    {
        return $this->currencyFee;
    }
}
