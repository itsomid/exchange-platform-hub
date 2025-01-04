<?php

namespace App\Services\Wallet\DTO\Wallet;

class WalletListsResponseDTO
{
    private ?int $id = null;

    private string $currency;

    private string $balance;

    private string $lockedBalance;

    private string $usdtBalance;

    private string $usdtLockedBalance;

    public function setId(?int $id): WalletListsResponseDTO
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCurrency(string $currency): WalletListsResponseDTO
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setBalance(string $balance): WalletListsResponseDTO
    {
        $this->balance = $balance;

        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setLockedBalance(string $lockedBalance): WalletListsResponseDTO
    {
        $this->lockedBalance = $lockedBalance;

        return $this;
    }

    public function getLockedBalance(): string
    {
        return $this->lockedBalance;
    }

    public function setUsdtBalance(string $usdtBalance): WalletListsResponseDTO
    {
        $this->usdtBalance = $usdtBalance;

        return $this;
    }

    public function getUsdtBalance(): string
    {
        return $this->usdtBalance;
    }

    public function setUsdtLockedBalance(string $usdtLockedBalance): WalletListsResponseDTO
    {
        $this->usdtLockedBalance = $usdtLockedBalance;

        return $this;
    }

    public function getUsdtLockedBalance(): string
    {
        return $this->usdtLockedBalance;
    }
}
