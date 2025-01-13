<?php

namespace App\Services\Exchanges\Asset\Contract;

interface AssetInterface
{
    public function getBalance(): array;
}
