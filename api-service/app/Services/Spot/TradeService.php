<?php

namespace App\Services\Spot;

use App\Repositories\Interfaces\SpotTradeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TradeService
{
    public function __construct(private readonly SpotTradeRepositoryInterface $tradeSpotRepository) {}

    public function getLatestMatched(int $marketId): Collection
    {
        return $this->tradeSpotRepository->getLatest($marketId);
    }
}
