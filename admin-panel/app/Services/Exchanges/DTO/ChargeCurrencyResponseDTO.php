<?php

namespace App\Services\Exchanges\DTO;

class ChargeCurrencyResponseDTO
{
    private string $withdrawStatus;

    public function setWithdrawStatus(string $withdrawStatus): ChargeCurrencyResponseDTO
    {
        $this->withdrawStatus = $withdrawStatus;

        return $this;
    }

    public function getWithdrawStatus(): string
    {
        return $this->withdrawStatus;
    }
} 