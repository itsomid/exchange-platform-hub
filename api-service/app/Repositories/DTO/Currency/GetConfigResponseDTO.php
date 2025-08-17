<?php

namespace App\Repositories\DTO\Currency;

class GetConfigResponseDTO
{
    private string $symbol;

    private string $maxAutoWithdrawAmount;

    private bool $interTransferEnabled;

    private int $precision;

    private array $chains;

    private string $currencyLogo;

    private string $currencyPersianName;

    private string $currencyName;


    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setInterTransferEnabled(bool $interTransferEnabled): self
    {
        $this->interTransferEnabled = $interTransferEnabled;

        return $this;
    }

    public function getInterTransferEnabled(): bool
    {
        return $this->interTransferEnabled;
    }

    public function setChains(array $chains): self
    {
        $this->chains = $chains;

        return $this;
    }

    public function getChains(): array
    {
        return $this->chains;
    }

    public function setMaxAutoWithdrawAmount(string $maxAutoWithdrawAmount): GetConfigResponseDTO
    {
        $this->maxAutoWithdrawAmount = $maxAutoWithdrawAmount;

        return $this;
    }

    public function getMaxAutoWithdrawAmount(): string
    {
        return $this->maxAutoWithdrawAmount;
    }

    public function setPrecision(int $precision): GetConfigResponseDTO
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setCurrencyLogo(string $currencyLogo): GetConfigResponseDTO
    {
        $this->currencyLogo = $currencyLogo;

        return $this;
    }

    public function getCurrencyLogo(): string
    {
        return $this->currencyLogo;
    }

    public function setCurrencyPersianName(string $currencyPersianName): GetConfigResponseDTO
    {
        $this->currencyPersianName = $currencyPersianName;

        return $this;
    }

    public function getCurrencyPersianName(): string
    {
        return $this->currencyPersianName;
    }

    public function setCurrencyName(string $currencyName): GetConfigResponseDTO
    {
        $this->currencyName = $currencyName;

        return $this;
    }

    public function getCurrencyName(): string
    {
        return $this->currencyName;
    }
}
