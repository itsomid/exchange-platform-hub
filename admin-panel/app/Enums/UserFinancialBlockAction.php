<?php

namespace App\Enums;

enum UserFinancialBlockAction :string
{
    case WITHDRAW = 'withdraw';
    case DEPOSIT = 'deposit';
    case TRADE = 'trade';

    const array TYPE_LABEL = [
        self::WITHDRAW->value => 'بلاک از برداشت',
        self::DEPOSIT->value => 'بلاک از واریز',
        self::TRADE->value => 'بلاک از معامله',
    ];

    public function label()
    {
        return self::TYPE_LABEL[$this->value] ??'';
    }
}
