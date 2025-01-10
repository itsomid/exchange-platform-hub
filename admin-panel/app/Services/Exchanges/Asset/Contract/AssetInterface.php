<?php

namespace App\Services\Exchanges\Asset\Contract;

use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;

interface AssetInterface
{
    public function getBalance(): array;
}
