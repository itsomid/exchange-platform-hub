<?php

namespace App\Services\Wallet\DTO\Wallet;

class GetOneWalletResponseDTO
{
    private string $symbol;
    private string $balance;
    private string $lockedBalance;
    private string $usdtBalance;
    private string $usdtLockedBalance;

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setLockedBalance(string $lockedBalance): self
    {
        $this->lockedBalance = $lockedBalance;
        return $this;
    }

    public function getLockedBalance(): string
    {
        return $this->lockedBalance;
    }

    public function setUsdtBalance(string $usdtBalance): self
    {
        $this->usdtBalance = $usdtBalance;
        return $this;
    }

    public function getUsdtBalance(): string
    {
        return $this->usdtBalance;
    }

    public function setUsdtLockedBalance(string $usdtLockedBalance): self
    {
        $this->usdtLockedBalance = $usdtLockedBalance;
        return $this;
    }

    public function getUsdtLockedBalance(): string
    {
        return $this->usdtLockedBalance;
    }
}
