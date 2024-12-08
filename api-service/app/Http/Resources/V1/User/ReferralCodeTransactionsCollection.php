<?php

namespace App\Http\Resources\V1\User;

use App\Services\User\DTO\ReferralCode\ReferralCodeGetOwnerProfitsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="ReferralCodeOwnerProfitsResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         example=100.75,
 *         description="The profit amount received from the referred user."
 *     ),
 *     @OA\Property(
 *         property="received_date",
 *         type="string",
 *         format="date-time",
 *         example="2024-11-25T14:21:17Z",
 *         description="The date when the profit was received."
 *     ),
 *     @OA\Property(
 *         property="transaction_description",
 *         type="string",
 *         example="Referral bonus for order #12345",
 *         description="A description of the transaction related to the profit."
 *     )
 * )
 */
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
