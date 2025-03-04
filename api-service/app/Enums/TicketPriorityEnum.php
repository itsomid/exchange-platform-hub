<?php

namespace App\Enums;

use App\Models\Deposit;

enum TicketPriorityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    // get type class
    const array TYPE_LABEL = [
        self::LOW->value => 'کم',
        self::MEDIUM->value => 'بالا',
        self::HIGH->value => 'زیاد',
    ];

    const array TYPE_COLOR = [
        self::LOW->value => 'info',
        self::MEDIUM->value => 'warning',
        self::HIGH->value => 'danger',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
