<?php

namespace App\Repositories\Interfaces;

use App\Enums\SpotOrderSideEnum;
use App\Models\SpotOrder;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO): SpotOrder;

    public function lists(TradeListRequestDTO $requestDTO): Collection;

    public function getDetail(int $userId, int $orderId): ?SpotOrder;

    public function getLatestOrders(int $marketId, int $limit): array;

    public function getOneWithLock(int $orderId): ?SpotOrder;

    public function hasOrdersOnOppositeSide(int $marketId, SpotOrderSideEnum $orderSide, ?int $excludeUserId = null): bool;
}
