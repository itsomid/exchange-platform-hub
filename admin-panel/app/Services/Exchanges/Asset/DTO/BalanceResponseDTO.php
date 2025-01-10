<?php

namespace App\Services\Exchanges\Asset\DTO;

class BalanceResponseDTO
{
    private string $ccy;
    private string $available;
    private string $frozen;

    public function setCcy(string $ccy): BalanceResponseDTO
    {
        $this->ccy = $ccy;
        return $this;
    }

    public function getCcy(): string
    {
        return $this->ccy;
    }

    public function setAvailable(string $available): BalanceResponseDTO
    {
        $this->available = $available;
        return $this;
    }

    public function getAvailable(): string
    {
        return $this->available;
    }

    public function setFrozen(string $frozen): BalanceResponseDTO
    {
        $this->frozen = $frozen;
        return $this;
    }

    public function getFrozen(): string
    {
        return $this->frozen;
    }
}
