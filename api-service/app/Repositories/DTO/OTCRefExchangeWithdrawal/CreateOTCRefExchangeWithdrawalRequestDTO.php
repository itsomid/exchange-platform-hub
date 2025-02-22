<?php

namespace App\Repositories\DTO\OTCRefExchangeWithdrawal;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;

class CreateOTCRefExchangeWithdrawalRequestDTO
{
    private int $transactionId;

    private OTCRefExchangeWithdrawalStatusEnum $status;

    private int $currencyId;

    public function setTransactionId(int $transactionId): CreateOTCRefExchangeWithdrawalRequestDTO
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function setStatus(OTCRefExchangeWithdrawalStatusEnum $status): CreateOTCRefExchangeWithdrawalRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): OTCRefExchangeWithdrawalStatusEnum
    {
        return $this->status;
    }

    public function setCurrencyId(int $currencyId): CreateOTCRefExchangeWithdrawalRequestDTO
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }
}
