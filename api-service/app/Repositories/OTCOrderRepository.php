<?php

namespace App\Repositories;

use App\Enums\OTCOrderStatusEnum;
use App\Models\OTCOrder;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use Illuminate\Support\Collection;

class OTCOrderRepository implements OTCOrderRepositoryInterface
{
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

    public function lists(int $userId): Collection
    {
        return OTCOrder::query()
            ->where('user_id', $userId)
            ->whereIn('status', [OTCOrderStatusEnum::SUCCESS, OTCOrderStatusEnum::PENDING])
            ->latest()
            ->get();
    }
}
