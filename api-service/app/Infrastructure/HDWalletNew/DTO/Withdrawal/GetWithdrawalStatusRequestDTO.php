<?php

namespace App\Infrastructure\HDWalletNew\DTO\Withdrawal;

class GetWithdrawalStatusRequestDTO
{
    private string $withdrawalId;

    public function getWithdrawalId(): string
    {
        return $this->withdrawalId;
    }

    public function setWithdrawalId(string $withdrawalId): self
    {
        $this->withdrawalId = $withdrawalId;
        return $this;
    }
}
