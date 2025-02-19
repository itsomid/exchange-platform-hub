<?php

namespace App\Services\Exchanges\DTO;

use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;

class ChargeUSDTResponse
{
    private WithdrawStatusEnum $withdrawStatus;

    public function setWithdrawStatus(WithdrawStatusEnum $withdrawStatus): ChargeUSDTResponse
    {
        $this->withdrawStatus = $withdrawStatus;

        return $this;
    }

    public function getWithdrawStatus(): WithdrawStatusEnum
    {
        return $this->withdrawStatus;
    }
}
