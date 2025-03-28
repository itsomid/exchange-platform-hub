<?php

namespace App\Enums;

enum LockedBalanceTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case ADMIN = 'admin';
    case SPOT = 'spot';

    public static function getFieldName(self $type): string
    {
        return match ($type) {
            self::WITHDRAWAL => 'withdrawal_id',
            self::SPOT => 'spot_order_id',
            self::ADMIN => 'admin_id',
        };
    }
}
