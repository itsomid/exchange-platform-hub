<?php

namespace App\Infrastructure\HDWallet\DTO\HDDeposit;

class GetDepositListsRequestDTO
{
    private string $currencySymbol;

    private string $walletAddress;

    public function setCurrencySymbol(string $currencySymbol): GetDepositListsRequestDTO
    {
        $this->currencySymbol = strtolower($currencySymbol);

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setWalletAddress(string $walletAddress): GetDepositListsRequestDTO
    {
        $this->walletAddress = $walletAddress;

        return $this;
    }

    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }
}
