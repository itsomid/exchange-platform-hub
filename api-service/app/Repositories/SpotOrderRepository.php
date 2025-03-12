<?php

namespace App\Repositories;

use App\Models\SpotOrder;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SpotOrderRepository implements SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO): SpotOrder
    {
        return SpotOrder::query()
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

    public function lists(TradeListRequestDTO $requestDTO): Collection
    {
        return SpotOrder::query()
            ->where('user_id', $requestDTO->getUserId())
            ->when($requestDTO->getSide(), fn (Builder $q) => $q->where('side', $requestDTO->getSide()))
            ->when($requestDTO->getType(), fn (Builder $q) => $q->where('type', $requestDTO->getType()))
            ->latest()
            ->with('market')
            ->get();
    }
}
