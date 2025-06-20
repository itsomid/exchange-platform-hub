<?php

namespace App\Infrastructure\HDWallet\DTO\HDDeposit;

class GetDepositListsRequestDTO
{
    private string $currencySymbol;

    private string $blockchain;

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

    public function setBlockchain(string $blockchain): GetDepositListsRequestDTO
    {
        $this->blockchain = $blockchain;

        return $this;
    }

    public function getBlockchain(): string
    {
        return $this->blockchain;
    }
}
