<?php

namespace App\Services\Exchanges\Asset\Contract;

use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;

interface AssetInterface
{
    public function getBalance(): array;

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO;

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse;
}
