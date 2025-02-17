<?php

namespace App\Repositories\Interfaces;

use App\Models\OTCRefExchangeWithdrawal;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;
use Illuminate\Database\Eloquent\Collection;

interface OTCRefExchangeWithdrawalInterface
{
    public function create(CreateOTCRefExchangeWithdrawalRequestDTO $requestDTO): OTCRefExchangeWithdrawal;

    public function getPending(): Collection;

    public function completeLists(array $ids): void;
}
