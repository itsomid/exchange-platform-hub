<?php

namespace App\Services\Exchanges\Asset\DTO;

use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;

class WithdrawRequestDTO
{
    private string $currency;

    private ?string $chain = null;

    private string $address;

    private WithdrawMethodEnum $withdrawMethod;

    private string $amount;

    public function setCurrency(string $currency): WithdrawRequestDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setChain(?string $chain): WithdrawRequestDTO
    {
        $this->chain = $chain;

        return $this;
    }

    public function getChain(): ?string
    {
        return $this->chain;
    }

    public function setAddress(string $address): WithdrawRequestDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setWithdrawMethod(WithdrawMethodEnum $withdrawMethod): WithdrawRequestDTO
    {
        $this->withdrawMethod = $withdrawMethod;

        return $this;
    }

    public function getWithdrawMethod(): WithdrawMethodEnum
    {
        return $this->withdrawMethod;
    }

    public function setAmount(string $amount): WithdrawRequestDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }
}
