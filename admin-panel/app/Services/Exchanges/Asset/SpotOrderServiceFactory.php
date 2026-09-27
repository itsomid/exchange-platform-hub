<?php

namespace App\Services\Exchanges\Asset;

use App\Services\Exchanges\Asset\Binance\BinanceSpotOrderService;
use App\Services\Exchanges\Asset\Coinex\CoinexSpotOrderService;
use App\Services\Exchanges\Asset\Contract\SpotOrderServiceInterface;
use InvalidArgumentException;

class SpotOrderServiceFactory
{
    public const SUPPORTED = ['coinex', 'binance'];

    public static function make(string $exchange): SpotOrderServiceInterface
    {
        return match ($exchange) {
            'coinex' => new CoinexSpotOrderService(),
            'binance' => new BinanceSpotOrderService(),
            default => throw new InvalidArgumentException("Exchange [{$exchange}] is not supported."),
        };
    }
}
