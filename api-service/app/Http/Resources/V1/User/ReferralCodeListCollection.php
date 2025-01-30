<?php

namespace App\Http\Resources\V1\User;

use App\Services\User\DTO\ReferralCode\ReferralCodeGetListsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="ReferralCodeListCollection",
 *
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier for the referral code"),
 *     @OA\Property(property="code", type="string", example="ABC12345", description="Referral code string"),
 *     @OA\Property(property="introducer_fee", type="integer", example=15, description="Fee for the introducer"),
 *     @OA\Property(property="friend_fee", type="integer", example=10, description="Fee for the friend"),
 *     @OA\Property(property="total_friends_usage", type="integer", example=5, description="Total number of friends who used the referral code"),
 *     @OA\Property(property="total_count_transaction", type="integer", example=20, description="Total count of transactions using the referral code"),
 *     @OA\Property(property="total_amount_received", type="integer", example=500, description="Total amount received through the referral code"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-25T12:34:56Z", description="Timestamp when the referral code was created")
 *
 * )
 */
class ReferralCodeListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_map(fn (ReferralCodeGetListsResponseDTO $item) => [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'introducer_fee' => $item->getIntroducerFee(),
            'friend_fee' => $item->getFriendFee(),
            'total_friends_usage' => $item->getTotalFriendUsage(),
            'total_count_transaction' => $item->getTotalCountTransaction(),
            'total_amount_received' => $item->getTotalAmountReceived(),
            'created_at' => $item->getCreatedAt(),
        ], $this->collection->toArray());
    }
}
