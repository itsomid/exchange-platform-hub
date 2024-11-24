<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case INVALID = 'invalid';
    case PAID = 'paid';
    case INSTALLED = 'installed';

    const array STATUS_LABEL = [
        self::INVALID->value => 'نا معتبر',
        self::PAID->value => 'پرداخت شده',
        self::INSTALLED->value => 'ایجاد شده',
    ];
}
