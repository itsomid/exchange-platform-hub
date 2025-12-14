<?php

namespace App\Repositories\Interfaces;

use App\Models\OTCOrder;
use App\Repositories\DTO\OTCOrder\CreateOTCOrderRequestDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OTCOrderRepositoryInterface
{
    public function create(CreateOTCOrderRequestDTO $requestDTO): OTCOrder;

    public function lists(int $userId, array $queryString): Collection;

    public function getOneById(int $id): OTCOrder;

    public function listsPaginated(int $userId, array $queryString, int $page = 1, int $perPage = 10): LengthAwarePaginator;
}
