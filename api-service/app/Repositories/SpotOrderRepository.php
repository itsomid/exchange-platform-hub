<?php

namespace App\Repositories;

use App\Models\SpotOrder;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;

class SpotOrderRepository implements SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO)
    {
        SpotOrder::query()
            ->create([
                'user_id' => $requestDTO->getUserId(),
                'market_id' => $requestDTO->getMarketId(),
                'side' => $requestDTO->getSide(),
                'type' => $requestDTO->getType(),
                'quantity' => $requestDTO->getQuantity(),
                'price' => $requestDTO->getPrice(),
                'status' => $requestDTO->getStatus(),
                'filled_quantity' => $requestDTO->getFilledQuantity(),
            ]);
    }
}
