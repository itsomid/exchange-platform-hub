<?php

namespace App\Repositories\Interfaces;

use App\Repositories\DTO\UserFinancialBlock\SaveOrUpdateBlockStateRequestDTO;

interface UserFinancialBlockRepositoryInterface
{
    public function saveOrUpdateState(SaveOrUpdateBlockStateRequestDTO $requestDTO): void;
}
