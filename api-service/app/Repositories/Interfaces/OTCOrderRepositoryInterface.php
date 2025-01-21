<?php

namespace App\Repositories\Interfaces;

use App\Models\OTCOrder;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use Illuminate\Support\Collection;

interface OTCOrderRepositoryInterface
{
    public function create(CreateOTCOrderRequestDTO $requestDTO): OTCOrder;

    public function lists(int $userId, array $queryString): Collection;
}
