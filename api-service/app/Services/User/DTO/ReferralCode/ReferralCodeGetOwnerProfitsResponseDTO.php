<?php

namespace App\Services\User\DTO\ReferralCode;

use Carbon\Carbon;

class ReferralCodeGetOwnerProfitsResponseDTO
{
    private string $amount;

    private Carbon $receivedDate;

    private string $transactionDescription;

    public function setAmount(string $amount): ReferralCodeGetOwnerProfitsResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setReceivedDate(Carbon $receivedDate): ReferralCodeGetOwnerProfitsResponseDTO
    {
        $this->receivedDate = $receivedDate;

        return $this;
    }

    public function getReceivedDate(): Carbon
    {
        return $this->receivedDate;
    }

    public function setTransactionDescription(string $transactionDescription): ReferralCodeGetOwnerProfitsResponseDTO
    {
        $this->transactionDescription = $transactionDescription;

        return $this;
    }

    public function getTransactionDescription(): string
    {
        return $this->transactionDescription;
    }
}
