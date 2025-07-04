<?php

namespace App\Http\Resources\V1\Stock;

use Illuminate\Http\Resources\Json\JsonResource;

class StockContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'stock_name' => $this->stock?->name ?? null,
            'stock_type' => $this->stock?->type ?? null,
            'stock_type_name' => __('enum.stock.stock_type.' . $this->stock->type->name ),
            'contract_number' => $this->contract_number,
            'contract_file' => $this->contract_file_url,
            'amount' => $this->amount,
            'total_value' => $this->total_value,
            'contract_status' => $this->contract_status->value,
            'cancellation_fee' => $this->cancellation_fee,
            'cancelled_at' => $this->cancelled_at,
            'sold_at' => $this->sold_at,
            'created_at' => $this->created_at,
        ];
    }
}
