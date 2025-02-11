<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;

enum TicketStatusEnum: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case RESOLVED = 'resolved';
    case REOPENED = 'reopened';
    // get type class
    const array TYPE_LABEL = [
        self::OPEN->value => 'باز شده',
        self::CLOSED->value => 'بسته شده',
        self::RESOLVED->value => 'حل شده',
        self::REOPENED->value => 'بازگشایی شده',
    ];

    const array TYPE_COLOR = [
        self::OPEN->value => 'primary',
        self::CLOSED->value => 'secondary',
        self::RESOLVED->value => 'success',
        self::REOPENED->value => 'warning',
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
