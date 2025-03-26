<?php

namespace App\Repositories\Interfaces;

use App\Models\SpotOrder;
use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO): SpotOrder;

    public function lists(TradeListRequestDTO $requestDTO): Collection;

    public function getLatestOrders(int $marketId, int $limit): array;
}
