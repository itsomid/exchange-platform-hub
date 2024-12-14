<?php

namespace App\Http\Resources\V1\User;

use App\Services\User\DTO\ReferralCode\ReferralCodeRegisteredUsersResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="ReferralCodeRegisteredUsersResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="total_orders",
 *         type="integer",
 *         example=5,
 *         description="Total number of transactions performed by the referred user."
 *     ),
 *     @OA\Property(
 *         property="total_profit",
 *         type="number",
 *         format="float",
 *         example=250.75,
 *         description="Total profit received from transactions performed by the referred user."
 *     ),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         description="Details of the referred user.",
 *         @OA\Property(property="name", type="string", example="John Doe", description="Name of the referred user."),
 *         @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email of the referred user.")
 *     )
 * )
 */
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
