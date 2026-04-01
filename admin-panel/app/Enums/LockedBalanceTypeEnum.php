<?php

namespace App\Enums;

enum LockedBalanceTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case ADMIN = 'admin';
    case SPOT = 'spot';

    const array TYPE_LABEL = [
        self::WITHDRAWAL->value => 'برداشت',
        self::ADMIN->value => 'مدیریت',
        self::SPOT->value => 'اسپات',
    ];

    public function label()
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    public static function getFieldName(self $type): string
    {
        return match ($type) {
            self::WITHDRAWAL => 'withdrawal_id',
            self::SPOT => 'spot_order_id',
            self::ADMIN => 'admin_id',
        };
    }

    const array TYPE_COLOR = [
        self::WITHDRAWAL->value => 'secondary',
        self::ADMIN->value => 'primary',
        self::SPOT->value => 'info',
    ];

    public function color()
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
