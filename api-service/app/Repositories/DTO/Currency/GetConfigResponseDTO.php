<?php

namespace App\Repositories\DTO\Currency;

class GetConfigResponseDTO
{
    private string $symbol;

    private string $maxAutoWithdrawAmount;

    private bool $interTransferEnabled;

    private int $pricePrecision;

    private int $amountPrecision;

    private int $quotePrecision;

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

    public function setPricePrecision(int $pricePrecision): GetConfigResponseDTO
    {
        $this->pricePrecision = $pricePrecision;

        return $this;
    }

    public function getPricePrecision(): int
    {
        return $this->pricePrecision;
    }

    public function setAmountPrecision(int $amountPrecision): GetConfigResponseDTO
    {
        $this->amountPrecision = $amountPrecision;

        return $this;
    }

    public function getAmountPrecision(): int
    {
        return $this->amountPrecision;
    }

    public function setQuotePrecision(int $quotePrecision): GetConfigResponseDTO
    {
        $this->quotePrecision = $quotePrecision;

        return $this;
    }

    public function getQuotePrecision(): int
    {
        return $this->quotePrecision;
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
