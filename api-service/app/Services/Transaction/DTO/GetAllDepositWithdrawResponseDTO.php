<?php

namespace App\Services\Transaction\DTO;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Carbon\Carbon;

class GetAllDepositWithdrawResponseDTO
{
    private string $currencySymbol;
    private string $currencyChain;

    private string $amount;

    private TransactionTypeEnum $type;

    private Carbon $createdAt;

    private TransactionStatusEnum $status;
    private ?string $address;
    private ?string $transactionHashed;
    private ?Carbon $confirmedAt;

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setType(TransactionTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): TransactionTypeEnum
    {
        return $this->type;
    }

    public function setCreatedAt(Carbon $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setStatus(TransactionStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): TransactionStatusEnum
    {
        return $this->status;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setTransactionHashed(?string $transactionHashed): self
    {
        $this->transactionHashed = $transactionHashed;
        return $this;
    }

    public function getTransactionHashed(): ?string
    {
        return $this->transactionHashed;
    }

    public function setConfirmedAt(?Carbon $confirmedAt): GetAllDepositWithdrawResponseDTO
    {
        $this->confirmedAt = $confirmedAt;
        return $this;
    }

    public function getConfirmedAt(): ?Carbon
    {
        return $this->confirmedAt;
    }

    public function setCurrencyChain(string $currencyChain): GetAllDepositWithdrawResponseDTO
    {
        $this->currencyChain = $currencyChain;
        return $this;
    }

    public function getCurrencyChain(): string
    {
        return $this->currencyChain;
    }
}
