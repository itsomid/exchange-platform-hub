<?php

namespace App\Services\Wallet\DTO\Wallet;

class WalletValueUSDTResponseDTO
{
    private string $amount;

    public function setAmount(string $amount): WalletValueUSDTResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }
}
