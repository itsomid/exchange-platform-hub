<?php

namespace App\Repositories\Interfaces;

use App\Models\OTCOrder;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;

interface OTCOrderRepositoryInterface
{
    public function create(CreateOTCOrderRequestDTO $requestDTO): OTCOrder;
}
