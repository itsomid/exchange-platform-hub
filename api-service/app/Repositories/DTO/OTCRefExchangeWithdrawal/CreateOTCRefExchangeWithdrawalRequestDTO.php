<?php

namespace App\Repositories\DTO\OTCRefExchangeWithdrawal;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;

class CreateOTCRefExchangeWithdrawalRequestDTO
{
    private int $transactionId;

    private OTCRefExchangeWithdrawalStatusEnum $status;

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
}
