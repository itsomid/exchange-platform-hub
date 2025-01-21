<?php

namespace App\Services\Wallet\DTO\Withdrawal;

use App\Enums\WithdrawalStatusEnum;

class CreateWithdrawalResponseDTO
{
    private int $id;
    private WithdrawalStatusEnum $status;

    private string $fee;

    private string $receivedAmount;

    public function setStatus(WithdrawalStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): WithdrawalStatusEnum
    {
        return $this->status;
    }

    public function setFee(string $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setReceivedAmount(string $receivedAmount): self
    {
        $this->receivedAmount = $receivedAmount;

        return $this;
    }

    public function getReceivedAmount(): string
    {
        return $this->receivedAmount;
    }

    public function setId(int $id): CreateWithdrawalResponseDTO
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
