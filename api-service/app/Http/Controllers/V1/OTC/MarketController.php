<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Requests\V1\OTC\AvailableBalanceRequest;
use App\Http\Resources\V1\OTC\AvailableCoinResource;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\OTC\OTCService;

class MarketController
{
    public function __construct(private readonly OTCService $OTCService) {}


    public function lists(): MarketCollection
    {
        return new MarketCollection($this->OTCService->markets());
    }

    public function prices(int $marketId)
    {
        $data = $this->OTCService->getPrices($marketId);
        if (isset($data['status']) && $data['status'] === 404) {
            return response($data, 404);
        }
        return response($data);
    }


    public function bitexroomAvailableBalance(AvailableBalanceRequest $request)
    {
        $validated = $request->validated();

        return new AvailableCoinResource(
            $this->OTCService->bitexroomAvailableBalance(
                $validated['market_id']
            )
        );
    }
}
