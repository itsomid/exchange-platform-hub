<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;
use App\Repositories\DTO\SpotOrder\TradeListRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO);

    public function lists(TradeListRequestDTO $requestDTO): Collection;
}
