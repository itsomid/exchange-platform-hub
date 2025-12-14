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
            'exchange_id' => $requestDTO->getExchangeId(),
            'quantity' => $requestDTO->getQuantity(),
            'price' => $requestDTO->getPrice(),
            'fee' => $requestDTO->getFee(),
            'type' => $requestDTO->getType(),
            'status' => $requestDTO->getStatus(),
        ]);
    }

    public function lists(int $userId, array $queryString): Collection
    {
        return OTCOrder::query()
            ->with('market')
            ->where('user_id', $userId)
            ->whereIn('status', [OTCOrderStatusEnum::SUCCESS, OTCOrderStatusEnum::PENDING])
            ->latest()
            ->filterBy($queryString)
            ->get();
    }

    public function listsPaginated(int $userId, array $queryString, int $page = 1, int $perPage = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = OTCOrder::query()
            ->with('market')
            ->where('user_id', $userId)
            ->whereIn('status', [OTCOrderStatusEnum::SUCCESS, OTCOrderStatusEnum::PENDING])
            ->latest()
            ->filterBy($queryString);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getOneById(int $id): OTCOrder
    {
        return OTCOrder::query()->find($id);
    }
}
