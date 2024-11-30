<?php

namespace App\Enums;

enum CurrencyChainEnum: string
{
    case BTC = 'BTC';
    case ERC20 = 'ERC20';
    case BEP20 = 'BEP20';
    case TRC20 = 'TRC20';
    case BSC = 'BSC';
    case DOGE = 'DOGE';
}
