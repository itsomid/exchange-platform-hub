<?php

namespace App\Services\Wallet\DTO\Withdrawal;

use App\Enums\WithdrawalStatusEnum;
use Carbon\Carbon;

class CheckWithdrawalResponseDTO
{
    private WithdrawalStatusEnum $status;

    private ?int $withdrawId = null;

    private ?string $amount = null;
    private ?string $totalFee = null;

    private ?string $currencySymbol = null;

    private ?string $currencyChain = null;

    private ?string $transactionHash = null;

    private ?string $walletAddress = null;

    private ?Carbon $confirmedAt = null;

    public function setStatus(WithdrawalStatusEnum $status): CheckWithdrawalResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): WithdrawalStatusEnum
    {
        return $this->status;
    }

    public function setWithdrawId(?int $withdrawId): CheckWithdrawalResponseDTO
    {
        $this->withdrawId = $withdrawId;

        return $this;
    }

    public function getWithdrawId(): ?int
    {
        return $this->withdrawId;
    }

    public function setAmount(?string $amount): CheckWithdrawalResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }
    public function setTotalFee(?string $total_fee): CheckWithdrawalResponseDTO
    {
        $this->totalFee = $total_fee;

        return $this;
    }

    public function getTotalFee(): ?string
    {
        return $this->totalFee;
    }

    public function setCurrencySymbol(?string $currencySymbol): CheckWithdrawalResponseDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    public function setCurrencyChain(?string $currencyChain): CheckWithdrawalResponseDTO
    {
        $this->currencyChain = $currencyChain;

        return $this;
    }

    public function getCurrencyChain(): ?string
    {
        return $this->currencyChain;
    }

    public function setTransactionHash(?string $transactionHash): CheckWithdrawalResponseDTO
    {
        $this->transactionHash = $transactionHash;

        return $this;
    }

    public function getTransactionHash(): ?string
    {
        return $this->transactionHash;
    }

    public function setWalletAddress(?string $walletAddress): CheckWithdrawalResponseDTO
    {
        $this->walletAddress = $walletAddress;

        return $this;
    }

    public function getWalletAddress(): ?string
    {
        return $this->walletAddress;
    }

    public function setConfirmedAt(?Carbon $confirmedAt): CheckWithdrawalResponseDTO
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getConfirmedAt(): ?Carbon
    {
        return $this->confirmedAt;
    }
}
