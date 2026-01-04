<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\Exchanges\MarketState\MarketStateService;
use App\Services\OTC\OTCService;

class MarketController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}


    public function lists(): MarketCollection
    {
        return new MarketCollection($this->OTCService->markets());
    }


    public function getState(int $marketId)
    {
        $marketStateService = resolve(MarketStateService::class);

        return response($marketStateService->getState($marketId));
    }
}
