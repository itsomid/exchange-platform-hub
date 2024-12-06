<?php

namespace App\Enums;

enum ExchangeFeeTypeEnum: string
{
    case TRADE = 'trade';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
}
