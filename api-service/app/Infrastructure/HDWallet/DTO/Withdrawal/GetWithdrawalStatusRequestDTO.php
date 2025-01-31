<?php

namespace App\Infrastructure\HDWallet\DTO\Withdrawal;

class GetWithdrawalStatusRequestDTO
{
    private int $withdrawalId;

    private string $currencySymbol;

    private string $blockchain;

    public function setWithdrawalId(int $withdrawalId): GetWithdrawalStatusRequestDTO
    {
        $this->withdrawalId = $withdrawalId;

        return $this;
    }

    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    public function setCurrencySymbol(string $currencySymbol): GetWithdrawalStatusRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setBlockchain(string $blockchain): GetWithdrawalStatusRequestDTO
    {
        $this->blockchain = $blockchain;

        return $this;
    }

    public function getBlockchain(): string
    {
        return $this->blockchain;
    }
}
