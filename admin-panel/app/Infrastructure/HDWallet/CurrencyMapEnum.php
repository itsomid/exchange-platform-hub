<?php

namespace App\Infrastructure\HDWallet;

enum CurrencyMapEnum: string
{
    case BSC = 'BINANCE';
    case DOGE = 'DOGE';
    case BTC = 'BITCOIN';
    case TRC20 = 'TRON';
    case ERC20 = 'ETHEREUM';
    case POLYGON = 'POLYGON';
    case ARBITRUM = 'ARBITRUM';
    case OPTIMISM = 'OPTIMISM';
    case AVALANCHE = 'AVALANCHE';
    case SONIC = 'SONIC';
    case LTC = 'LITECOIN';
    case DASH = 'DASH';
}
