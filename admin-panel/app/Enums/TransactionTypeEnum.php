<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case BUY = 'buy';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL= 'withdrawal';
    case REFERRAL = 'referral';

    const array TYPE_LABEL = [
        self::BUY->value => 'خرید',
        self::DEPOSIT->value => 'واریز',
    ];

    const array TYPE_COLOR = [
        self::BUY->value => 'primary',
        self::DEPOSIT->value => 'success',
    ];
}
