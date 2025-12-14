<?php

namespace App\Http\Resources\V1\OTC;

use App\Services\OTC\DTO\MarketResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class MarketCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn(MarketResponseDTO $DTO) => [
            'market_id' => $DTO->getMarketId(),
            'price_precision' => $DTO->getPricePrecision(),
            'amount_precision' => $DTO->getAmountPrecision(),
            'quote_precision' => $DTO->getQuotePrecision(),
            'base_currency' => $DTO->getBaseCurrency(),
            'currency_name' => $DTO->getCurrencyName(),
            'currency_persian_name' => $DTO->getCurrencyPersianName(),
            'currency_logo' => $DTO->getCurrencyLogo(),
            'quote_currency' => $DTO->getQuoteCurrency(),
            'is_active' => $DTO->getIsActive(),
            'min_trade_amount' => $DTO->getMinTradeAmount(),
            'max_trade_amount' => $DTO->getMaxTradeAmount(),
            'min_otc_amount' => $DTO->getMinOTCAmount(),
            'max_otc_amount' => $DTO->getMaxOTCAmount(),
        ])->toArray();
    }
}
