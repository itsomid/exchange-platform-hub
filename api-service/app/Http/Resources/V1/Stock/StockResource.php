<?php

namespace App\Http\Resources\V1\Stock;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
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
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type,
            'cancellation_fee' => $this->cancellation_fee,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
} 