<?php

namespace App\Repositories;

use App\Models\OTCOrder;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;

class OTCOrderRepository implements OTCOrderRepositoryInterface {
    public function create(CreateOTCOrderRequestDTO $requestDTO): OTCOrder
    {
        return OTCOrder::query()->create([
            'user_id' => $requestDTO->getUserId(),
            'market_id' => $requestDTO->getMarketId(),
            'quantity' => $requestDTO->getQuantity(),
            'price' => $requestDTO->getPrice(),
            'fee' => $requestDTO->getFee(),
            'type' => $requestDTO->getType(),
            'status' => $requestDTO->getStatus(),
        ]);
    }
}
