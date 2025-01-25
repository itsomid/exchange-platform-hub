<?php

namespace App\Services\Transaction\DTO;

use App\Enums\TransactionTypeEnum;

class GetAllDepositWithdrawRequestDTO
{
    private int $userId;

    private ?TransactionTypeEnum $transactionType = null;

    private ?string $currencySymbol = null;

    public function setUserId(int $userId): GetAllDepositWithdrawRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setTransactionType(?TransactionTypeEnum $transactionType): GetAllDepositWithdrawRequestDTO
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    public function getTransactionType(): ?TransactionTypeEnum
    {
        return $this->transactionType;
    }

    public function setCurrencySymbol(?string $currencySymbol): GetAllDepositWithdrawRequestDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }
}
