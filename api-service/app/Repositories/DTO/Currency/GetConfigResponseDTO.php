<?php

namespace App\Repositories\DTO\Currency;

class GetConfigResponseDTO
{
    private string $name;

    private string $symbol;

    private string $maxAutoWithdrawAmount;

    private bool $interTransferEnabled;

    private int $precision;

    private array $chains;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

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
}
