<?php

namespace App\Repositories\Interfaces;

use App\Models\OTCRefExchangeWithdrawal;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;

interface OTCRefExchangeWithdrawalInterface
{
    public function create(CreateOTCRefExchangeWithdrawalRequestDTO $requestDTO): OTCRefExchangeWithdrawal;
}
