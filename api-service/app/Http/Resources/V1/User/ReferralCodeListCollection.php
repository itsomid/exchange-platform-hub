<?php

namespace App\Http\Resources\V1\User;

use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="ReferralCodeListCollection",
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
        return $this->collection->map(fn (ReferralCode $item) => [
            'id' => $item->id,
            'code' => $item->code,
            'introducer_fee' => $item->introducer_fee,
            'friend_fee' => $item->friend_fee,
            'total_friends_usage' => $item->registered_users_count,
            'total_count_transaction' => $item->referral_code_usage_count,
            'total_amount_received' => (int) $item->transactions_sum_amount,
            'created_at' => $item->created_at,
        ])->toArray();
    }
}
