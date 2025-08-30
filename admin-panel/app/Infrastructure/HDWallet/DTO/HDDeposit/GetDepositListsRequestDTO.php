<?php

namespace App\Infrastructure\HDWallet\DTO\HDDeposit;

use App\Models\CurrencyChain;

class GetDepositListsRequestDTO
{
    private string $network;

    private string $tokenSymbol;

    private ?string $contractAddress = null;

    private string $address;

    private int $limit = 50;

    private ?CurrencyChain $currencyChain = null;

    public function setNetwork(string $network): GetDepositListsRequestDTO
    {
        $this->network = strtolower($network);

        return $this;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setTokenSymbol(string $tokenSymbol): GetDepositListsRequestDTO
    {
        $this->tokenSymbol = strtoupper($tokenSymbol);

        return $this;
    }

    public function getTokenSymbol(): string
    {
        return $this->tokenSymbol;
    }

    public function setContractAddress(?string $contractAddress): GetDepositListsRequestDTO
    {
        $this->contractAddress = $contractAddress;

        return $this;
    }

    public function getContractAddress(): ?string
    {
        return $this->contractAddress;
    }

    public function setAddress(string $address): GetDepositListsRequestDTO
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setLimit(int $limit): GetDepositListsRequestDTO
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setCurrencyChain(?CurrencyChain $currencyChain): GetDepositListsRequestDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): ?CurrencyChain
    {
        return $this->currencyChain;
    }

    // Legacy methods for backward compatibility
    public function setCurrencySymbol(string $currencySymbol): GetDepositListsRequestDTO
    {
        return $this->setTokenSymbol($currencySymbol);
    }

    public function getCurrencySymbol(): string
    {
        return $this->getTokenSymbol();
    }

    public function setWalletAddress(string $walletAddress): GetDepositListsRequestDTO
    {
        return $this->setAddress($walletAddress);
    }

    public function getWalletAddress(): string
    {
        return $this->getAddress();
    }

    public function setBlockchain(string $blockchain): GetDepositListsRequestDTO
    {
        return $this->setNetwork($blockchain);
    }

    public function getBlockchain(): string
    {
        return $this->getNetwork();
    }
}
