<?php

namespace App\Services\Exchanges\DTO;

use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;

class ChargeUSDTResponseDTO
{
    private WithdrawStatusEnum $withdrawStatus;

    public function setWithdrawStatus(WithdrawStatusEnum $withdrawStatus): ChargeUSDTResponseDTO
    {
        $this->withdrawStatus = $withdrawStatus;

        return $this;
    }

    public function getWithdrawStatus(): WithdrawStatusEnum
    {
        return $this->withdrawStatus;
    }
}
