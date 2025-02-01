<?php

namespace App\Repositories\DTO\Withdrawal;

use App\Enums\WithdrawalStatusEnum;

class CreateWithdrawalRequestDTO
{
    private int $user_id;

    private string $currencyChain;

    private string $currencySymbol;

    private string $amount;

    private string $USDTValue;

    private string $networkFee;

    private string $exchangeFee;

    private string $address;

    private WithdrawalStatusEnum $status;

    public function setUserId(int $user_id): CreateWithdrawalRequestDTO
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setCurrencyChain(string $currencyChain): CreateWithdrawalRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }

    public function setCurrencySymbol(string $currencySymbol): CreateWithdrawalRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setAmount(string $amount): CreateWithdrawalRequestDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setNetworkFee(string $networkFee): CreateWithdrawalRequestDTO
    {
        $this->networkFee = $networkFee;

        return $this;
    }

    public function getNetworkFee(): string
    {
        return $this->networkFee;
    }

    public function setAddress(string $address): CreateWithdrawalRequestDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setStatus(WithdrawalStatusEnum $status): CreateWithdrawalRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): WithdrawalStatusEnum
    {
        return $this->status;
    }

    public function setExchangeFee(string $exchangeFee): CreateWithdrawalRequestDTO
    {
        $this->exchangeFee = $exchangeFee;

        return $this;
    }

    public function getExchangeFee(): string
    {
        return $this->exchangeFee;
    }

    public function setUSDTValue(string $USDTValue): CreateWithdrawalRequestDTO
    {
        $this->USDTValue = $USDTValue;

        return $this;
    }

    public function getUSDTValue(): string
    {
        return $this->USDTValue;
    }
}
