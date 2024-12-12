<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;

interface UserFinancialBlockRepositoryInterface
{
    public function saveNewState(SaveOrUpdateBlockStateRequestDTO $requestDTO): void;
}
