<?php

namespace App\Repositories;

use App\Models\OTCRefExchangeWithdrawal;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;

class OTCRefExchangeWithdrawalRepository implements OTCRefExchangeWithdrawalInterface
{
    public function create(CreateOTCRefExchangeWithdrawalRequestDTO $requestDTO): OTCRefExchangeWithdrawal
    {
        return OTCRefExchangeWithdrawal::query()
            ->create([
                'transaction_id' => $requestDTO->getTransactionId(),
                'status' => $requestDTO->getStatus(),
            ]);
    }
}
