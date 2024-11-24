<?php

namespace App\Enums;

enum DepositTypeEnum: string
{
    case Admin = 'admin';
    case Gift = 'gift';
    case Debit = 'debit';
    case BUY = 'buy';

    const array TYPE_LABEL = [
        self::Admin->value => 'توسط ادمین',
        self::Gift->value => 'هدیه',
        self::Debit->value => 'کارت به کارت',
        self::BUY->value => 'درگاه',
    ];

    const array TYPE_COLOR = [
        self::Admin->value => 'primary',
        self::Gift->value => 'warning',
        self::Debit->value => 'danger',
        self::BUY->value => 'success',
    ];
}
