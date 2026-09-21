<?php

namespace App\Services\Exchanges\Asset\Contract;

use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;

interface AssetInterface
{
    public function getBalance(): array;

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse;
}
