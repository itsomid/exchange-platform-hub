<?php

namespace App\Http\Resources\V1\Withdrawal;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;


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
