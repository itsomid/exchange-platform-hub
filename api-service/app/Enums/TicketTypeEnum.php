<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;

enum TicketTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case OTC_ORDER = 'otc_order';

    // get type class
    public static function getTypeClass(string $type): string
    {
        return match ($type) {
            self::WITHDRAWAL->value => Withdrawal::class,
            self::DEPOSIT->value => Deposit::class,
            self::OTC_ORDER->value => OTCOrder::class,
        };
    }
}
