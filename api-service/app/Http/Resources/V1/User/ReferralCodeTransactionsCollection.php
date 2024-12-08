<?php

namespace App\Http\Resources\V1\User;

use App\Services\User\DTO\ReferralCode\ReferralCodeGetOwnerProfitsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReferralCodeTransactionsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (ReferralCodeGetOwnerProfitsResponseDTO $item) => [
            'amount' => $item->getAmount(),
            'received_date' => $item->getReceivedDate(),
            'transaction_description' => $item->getTransactionDescription(),
        ])->toArray();
    }
}
