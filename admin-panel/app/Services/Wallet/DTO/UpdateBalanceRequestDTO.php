<?php

namespace App\Services\Wallet\DTO;

use App\Enums\BalanceOperationEnum;

class UpdateBalanceRequestDTO
{
    private string $currencySymbol;

    private int $userId;

    private string $amount;

    private BalanceOperationEnum $operation;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getOperation(): BalanceOperationEnum
    {
        return $this->operation;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setOperation(BalanceOperationEnum $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }
}
