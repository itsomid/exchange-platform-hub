<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case BUY = 'buy';
    case SELL = 'sell';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case REFERRAL = 'referral';
    case FEE = 'fee';
}
