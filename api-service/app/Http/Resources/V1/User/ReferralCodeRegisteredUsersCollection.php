<?php

namespace App\Http\Resources\V1\User;

use App\Services\User\DTO\ReferralCode\ReferralCodeRegisteredUsersResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReferralCodeRegisteredUsersCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (ReferralCodeRegisteredUsersResponseDTO $dto) => [
            'total_orders' => $dto->getTotalOrders(),
            'total_profit' => $dto->getTotalProfit(),
            'user' => [
                'name' => $dto->getUserName(),
                'email' => $dto->getUserEmail(),
            ],
        ])->toArray();
    }
}
