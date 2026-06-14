<?php

namespace App\Http\Controllers\V1\Market;

use App\Http\Resources\V1\OTC\MarketCollection;
use App\Models\Market;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Services\OTC\DTO\MarketResponseDTO;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Http\JsonResponse;

class HomeMarketController
{
    public function __construct(
        private readonly MarketRepositoryInterface $marketRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {}

    public function index(): JsonResponse
    {
        $markets = $this->marketRepository->getHomeMarkets();

        $usdtCurrency = $this->currencyRepository->getOne('USDT');
        $usdtPrecision = $usdtCurrency ? $usdtCurrency->amount_precision : 8;

        $data = $markets->map(
            fn(Market $market) => resolve(MarketResponseDTO::class)
                ->setMarketId($market->id)
                ->setBaseCurrency($market->base_currency)
                ->setPricePrecision($market->currency->price_precision)
                ->setAmountPrecision($market->currency->amount_precision)
                ->setQuotePrecision($usdtPrecision)
                ->setCurrencyName($market->currency->name)
                ->setCurrencyPersianName($market->currency->persian_name)
                ->setCurrencyLogo($market->currency->logo)
                ->setQuoteCurrencyLogo($usdtCurrency?->logo ?? null)
                ->setQuoteCurrency($market->quote_currency)
                ->setIsActive($market->is_active)
                ->setMinTradeAmount($market->min_trade_amount)
                ->setMaxTradeAmount($market->max_trade_amount)
                ->setMinOTCAmount($market->min_otc_amount)
                ->setMaxOTCAmount($market->max_otc_amount)
        )->map(fn(MarketResponseDTO $DTO) => [
            'market_id' => $DTO->getMarketId(),
            'base_currency' => $DTO->getBaseCurrency(),
            'quote_currency' => $DTO->getQuoteCurrency(),
            'currency_name' => $DTO->getCurrencyName(),
            'currency_persian_name' => $DTO->getCurrencyPersianName(),
            'currency_logo' => $DTO->getCurrencyLogo() ? config('bitexroom.currency_logo_base_url') . '/' . $DTO->getCurrencyLogo() : null,
        ])->values();

        return response()->json(['data' => $data]);
    }
}
