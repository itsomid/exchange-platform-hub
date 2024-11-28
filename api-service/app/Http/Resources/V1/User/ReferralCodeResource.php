<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *      schema="ReferralCodeResource",
 *      type="object",
 *
 *      @OA\Property(property="code", type="string", example="XYZ123", description="The generated referral code."),
 *      @OA\Property(property="introducer_fee", type="integer", example=15, description="The fee received by the introducer."),
 *      @OA\Property(property="friend_fee", type="integer", example=10, description="The fee the friend will pay."),
 *  )
 *
 * @property string $code
 * @property int    $introducer_fee
 * @property int    $friend_fee
 */
class ReferralCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'introducer_fee' => $this->introducer_fee,
            'friend_fee' => $this->friend_fee,
        ];
    }
}
