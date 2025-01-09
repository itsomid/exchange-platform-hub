<?php

namespace App\Repositories\DTO\Currency;

use App\Enums\CurrencyChainEnum;

class ChainResponseDTO
{
    private CurrencyChainEnum $chain;

    private string $minDepositAmount;

    private string $minWithdrawAmount;

    private bool $depositEnabled;

    private bool $withdrawEnabled;

    private int $depositDelayMinutes;

    private int $safeConfirmations;

    private string $withdrawalFee;

    private int $withdrawPrecision;

    private ?string $memo;

    private bool $isMemoRequiredForDeposit;

    public function setChain(CurrencyChainEnum $chain): self
    {
        $this->chain = $chain;

        return $this;
    }

    public function getChain(): CurrencyChainEnum
    {
        return $this->chain;
    }

    public function setChainName(string $chainName): self
    {
        $this->chain_name = $chainName;

        return $this;
    }

    public function getChainName(): string
    {
        return $this->chain_name;
    }

    public function setMinDepositAmount(string $minDepositAmount): self
    {
        $this->minDepositAmount = $minDepositAmount;

        return $this;
    }

    public function getMinDepositAmount(): string
    {
        return $this->minDepositAmount;
    }

    public function setMinWithdrawAmount(string $minWithdrawAmount): self
    {
        $this->minWithdrawAmount = $minWithdrawAmount;

        return $this;
    }

    public function getMinWithdrawAmount(): string
    {
        return $this->minWithdrawAmount;
    }

    public function setDepositEnabled(bool $depositEnabled): self
    {
        $this->depositEnabled = $depositEnabled;

        return $this;
    }

    public function getDepositEnabled(): bool
    {
        return $this->depositEnabled;
    }

    public function setWithdrawEnabled(bool $withdrawEnabled): self
    {
        $this->withdrawEnabled = $withdrawEnabled;

        return $this;
    }

    public function getWithdrawEnabled(): bool
    {
        return $this->withdrawEnabled;
    }

    public function setDepositDelayMinutes(int $depositDelayMinutes): self
    {
        $this->depositDelayMinutes = $depositDelayMinutes;

        return $this;
    }

    public function getDepositDelayMinutes(): int
    {
        return $this->depositDelayMinutes;
    }

    public function setSafeConfirmations(int $safeConfirmations): self
    {
        $this->safeConfirmations = $safeConfirmations;

        return $this;
    }

    public function getSafeConfirmations(): int
    {
        return $this->safeConfirmations;
    }

    public function setWithdrawPrecision(int $withdrawPrecision): self
    {
        $this->withdrawPrecision = $withdrawPrecision;

        return $this;
    }

    public function getWithdrawPrecision(): int
    {
        return $this->withdrawPrecision;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setIsMemoRequiredForDeposit(bool $isMemoRequiredForDeposit): self
    {
        $this->isMemoRequiredForDeposit = $isMemoRequiredForDeposit;

        return $this;
    }

    public function getMemoRequiredForDeposit(): bool
    {
        return $this->isMemoRequiredForDeposit;
    }

    public function setWithdrawalFee(string $withdrawalFee): self
    {
        $this->withdrawalFee = $withdrawalFee;

        return $this;
    }

    public function getWithdrawalFee(): string
    {
        return $this->withdrawalFee;
    }
}
