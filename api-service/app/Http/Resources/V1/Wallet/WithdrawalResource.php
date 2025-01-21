<?php

namespace App\Http\Resources\V1\Wallet;

use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="WithdrawalResource",
 *
 *          @OA\Property(
 *          property="id",
 *          type="int",
 *          description="The withdrawal's id",
 *          example=1
 *      ),
 *     @OA\Property(
 *         property="user_received_amount",
 *         type="number",
 *         format="float",
 *         description="The amount the user will receive after the withdrawal fee is deducted.",
 *         example=0.009
 *     ),
 *     @OA\Property(
 *         property="fee",
 *         type="number",
 *         format="float",
 *         description="The fee charged for the withdrawal.",
 *         example=0.001
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "awaiting_approval"},
 *         description="The status of the withdrawal.",
 *         example="pending"
 *     )
 * )
 */
class WithdrawalResource extends JsonResource
{
    public function __construct(CreateWithdrawalResponseDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'user_received_amount' => $this->resource->getReceivedAmount(),
            'fee' => $this->resource->getFee(),
            'status' => $this->resource->getStatus()->value,
        ];
    }
}
