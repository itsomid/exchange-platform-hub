<?php

namespace App\Services\Exchanges\Asset\DTO;

use Carbon\Carbon;

class WithdrawResponseDTO
{
    private string $withdrawId;
    private Carbon $createdAt;
    private string $currency;
    private string $chain;
    private string $amount;
    private string $actualAmount;
    private string $fee;
    private ?string $currencyFee = null;
    private ?string $withdrawMethod = null;
    private string $address;
    private int $confirmationCount;
    private string $exploreAddress;
    private string $status;

    public function setWithdrawId(string $withdrawId): WithdrawResponseDTO
    {
        $this->withdrawId = $withdrawId;
        return $this;
    }

    public function getWithdrawId(): string
    {
        return $this->withdrawId;
    }

    public function setCreatedAt(int $createdAt): WithdrawResponseDTO
    {
        $this->createdAt = Carbon::createFromTimestampMs($createdAt)->setTimezone(config('app.timezone', 'Asia/Tehran'));
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

    public function setWithdrawMethod(?string $withdrawMethod): WithdrawResponseDTO
    {
        $this->withdrawMethod = "on_chain";
        return $this;
    }

    public function getWithdrawMethod(): ?string
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
        $this->status = $status;
        return $this;
    }

    public function getStatus(): string
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
