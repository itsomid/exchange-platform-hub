<?php

namespace App\Repositories;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Models\OTCRefExchangeWithdrawal;
use App\Repositories\DTO\OTCRefExchangeWithdrawal\CreateOTCRefExchangeWithdrawalRequestDTO;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;
use Illuminate\Database\Eloquent\Collection;

class OTCRefExchangeWithdrawalRepository implements OTCRefExchangeWithdrawalInterface
{
    public function create(CreateOTCRefExchangeWithdrawalRequestDTO $requestDTO): OTCRefExchangeWithdrawal
    {
        return OTCRefExchangeWithdrawal::query()
            ->create([
                'currency_id' => $requestDTO->getCurrencyId(),
                'transaction_id' => $requestDTO->getTransactionId(),
                'status' => $requestDTO->getStatus(),
            ]);
    }

    public function getPending(): Collection
    {
        return OTCRefExchangeWithdrawal::query()
            ->where('status', OTCRefExchangeWithdrawalStatusEnum::PENDING)
            ->get();
    }

    public function completeLists(array $ids): void
    {
        OTCRefExchangeWithdrawal::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => OTCRefExchangeWithdrawalStatusEnum::COMPLETED,
            ]);
    }
}
