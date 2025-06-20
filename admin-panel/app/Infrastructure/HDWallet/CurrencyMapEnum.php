<?php

namespace App\Infrastructure\HDWallet;

enum CurrencyMapEnum: string
{
    case BSC = 'BINANCE';
    case DOGE = 'DOGE';
    case BTC = 'BITCOIN';
    case TRC20 = 'TRON';
    case ERC20 = 'ETHEREUM';
}
