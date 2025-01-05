<?php

namespace App\Services\Transaction\DTO;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Carbon\Carbon;

class GetAllDepositWithdrawResponseDTO
{
    private string $currencySymbol;

    private string $amount;

    private TransactionTypeEnum $type;

    private Carbon $createdAt;

    private TransactionStatusEnum $status;

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
}
