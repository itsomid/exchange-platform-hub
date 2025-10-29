<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class SpotOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'market' => $this->getMarketName(),
            'side' => $this->getSide()->value,
            'side_label' => __('enum.spot.side.' . $this->getSide()->name),
            'type' => $this->getType()->value,
            'type_label' => __('enum.spot.type.' . $this->getType()->name),
            'quantity' => $this->getQuantity(),
            'price' => $this->getPrice(),
            'status' => $this->getStatus()->value,
            'status_label' => __('enum.spot.status.' . $this->getStatus()->name),
            'filled_quantity' => $this->getFilledQuantity(),
            'commission' => $this->getCommission(),
            'commission_currency' => $this->getCommissionCurrency(),
            'filled_value' => $this->getFilledValue(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
