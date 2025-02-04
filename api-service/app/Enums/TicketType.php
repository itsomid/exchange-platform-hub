<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;

enum TicketType: string
{
    case Withdrawal = 'withdrawal';
    case Deposit = 'deposit';
    case OTC = 'otc';

    // get type class
    public static function getTypeClass(string $type): string
    {
        return match ($type) {
            self::Withdrawal->value => Withdrawal::class,
            self::Deposit->value => Deposit::class,
            self::OTC->value => OTCOrder::class,
        };
    }
}
