<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;

enum TicketTypeEnum: string
{
    case Withdrawal = 'withdrawal';
    case Deposit = 'deposit';
    case OTC = 'otc';

    // get type class
    const array TYPE_LABEL = [
        self::Withdrawal->value => 'برداشت',
        self::Deposit->value => 'واریز',
        self::OTC->value => 'معامله',

    ];

    const array TYPE_COLOR = [
        self::Withdrawal->value => 'danger',
        self::Deposit->value => 'success',
        self::OTC->value => 'info',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     *
     * @return string
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
