<?php

namespace App\Services\Exchanges\Asset;

use App\Services\Exchanges\Asset\Coinex\AssetCoinex;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use InvalidArgumentException;

class AssetFactory
{
    public static function make(string $exchange): AssetInterface
    {
        return match ($exchange) {
            'coinex' => new AssetCoinex,
            // Add other exchanges like 'kucoin', 'binance', etc.
            default => throw new InvalidArgumentException("Exchange [{$exchange}] is not supported."),
        };
    }
}
