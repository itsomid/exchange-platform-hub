<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\SpotOrder\SpotOrderCreateRequestDTO;

interface SpotOrderRepositoryInterface
{
    public function create(SpotOrderCreateRequestDTO $requestDTO);
}
