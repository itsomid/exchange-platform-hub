<?php

namespace App\Services\Exchanges\Asset\Contract;

use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;

interface AssetInterface
{
    public function getBalance(): array;

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO;
}
