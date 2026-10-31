<?php

namespace App\Http\Controllers\V1\Market;

use App\Models\Market;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Services\OTC\DTO\MarketResponseDTO;
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

        $data = $markets->map(function (Market $market) use ($usdtCurrency, $usdtPrecision): array {
            $dto = resolve(MarketResponseDTO::class)
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
                ->setMaxOTCAmount($market->max_otc_amount);

            $exchangePrice = $market->exchangePrice;
            $lastPrice = $exchangePrice?->price;
            $openPrice = $exchangePrice?->open_price;
            $priceChangePercentage = (! is_null($lastPrice) && ! is_null($openPrice) && (float) $openPrice > 0)
                ? round((((float) $lastPrice - (float) $openPrice) / (float) $openPrice) * 100, 2)
                : 0;

            return [
                'market_id' => $dto->getMarketId(),
                'base_currency' => $dto->getBaseCurrency(),
                'quote_currency' => $dto->getQuoteCurrency(),
                'currency_name' => $dto->getCurrencyName(),
                'currency_persian_name' => $dto->getCurrencyPersianName(),
                'currency_logo' => $dto->getCurrencyLogo()
                    ? config('bitexroom.currency_logo_base_url') . '/' . $dto->getCurrencyLogo()
                    : null,
                'last_price' => $lastPrice,
                'price' => $lastPrice,
                'buy_price' => $exchangePrice?->buy_price,
                'sell_price' => $exchangePrice?->sell_price,
                'price_change_percentage' => $priceChangePercentage,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
