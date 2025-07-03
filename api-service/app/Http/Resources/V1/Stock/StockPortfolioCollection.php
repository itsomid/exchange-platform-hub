<?php

namespace App\Http\Resources\V1\Stock;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockPortfolioCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => [
                'contracts' => StockContractResource::collection($this->collection['contracts']),
                'total_value' => $this->collection['total_value'],
                'wallet_balance' => $this->collection['wallet_balance'],
            ]
        ];
    }
} 