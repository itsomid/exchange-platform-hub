<?php

namespace App\Services\OTC;

use App\Models\Market;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Services\OTC\DTO\MarketResponseDTO;

class OTCService
{
    public function __construct(private readonly MarketRepositoryInterface $marketRepository) {}

    public function markets(): array
    {
        $markets = $this->marketRepository->getOTCMarkets();

        return $markets->map(fn (Market $market) => resolve(MarketResponseDTO::class)
            ->setBaseCurrency($market->base_currency)
            ->setQuoteCurrency($market->quote_currency)
            ->setIsActive($market->is_active)
            ->setSellPrice(bcmul($market->exchangePrice->price, (string) ($market->exchangePrice->exchange_profit_sell + 1), 8))
            ->setBuyPrice(bcmul($market->exchangePrice->price, (string) ($market->exchangePrice->exchange_profit_buy + 1), 8))
            ->setMinTradeAmount($market->min_trade_amount)
            ->setMaxTradeAmount($market->max_trade_amount)
        )->toArray();
    }
}
