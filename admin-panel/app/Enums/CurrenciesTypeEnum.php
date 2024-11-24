<?php

namespace App\Enums;

enum CurrenciesTypeEnum: string
{
    case ERC20 = 'erc20';
    case BEP20 = 'bep20';
    case TRC20 = 'trc20';
    case BTC = 'btc';
    case DOGECOIN = 'dogecoin';
}
