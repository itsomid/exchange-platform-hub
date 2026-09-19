<?php

namespace App\Enums;

enum DepositTypeEnum: string
{
    case MANUAL_ADMIN = 'manual_admin';
    case USER_INITIATED = 'user_initiated';

    const array TYPE_LABEL = [
        self::MANUAL_ADMIN->value => 'واریز ادمین',
        self::USER_INITIATED->value => 'واریز کاربر',
    ];

    const array TYPE_COLOR = [
        self::MANUAL_ADMIN->value => 'danger',
        self::USER_INITIATED->value => 'primary',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
