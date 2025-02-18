<?php

namespace App\Services\Wallet\DTO\Withdrawal;

use App\Enums\WithdrawalStatusEnum;

class CheckWithdrawalResponseDTO
{
    private WithdrawalStatusEnum $status;

    public function setStatus(WithdrawalStatusEnum $status): CheckWithdrawalResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): WithdrawalStatusEnum
    {
        return $this->status;
    }
}
