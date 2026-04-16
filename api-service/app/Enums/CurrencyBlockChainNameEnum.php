<?php

namespace App\Enums;

enum CurrencyBlockChainNameEnum: string
{
    case BITCOIN = 'BITCOIN';
    case TRON = 'TRON';
    case BINANCE = 'BINANCE';
    case ETHEREUM = 'ETHEREUM';
    case DOGE = 'DOGE';
    case POLYGON = 'POLYGON';
    case ARBITRUM = 'ARBITRUM';
    case OPTIMISM = 'OPTIMISM';
}
