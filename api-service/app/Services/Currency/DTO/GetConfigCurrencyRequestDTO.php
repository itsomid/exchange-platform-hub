<?php

namespace App\Services\Currency\DTO;

class GetConfigCurrencyRequestDTO
{
    private string $symbol;

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }
}
