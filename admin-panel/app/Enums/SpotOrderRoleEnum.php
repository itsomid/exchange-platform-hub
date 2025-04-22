<?php

namespace App\Enums;

enum SpotOrderRoleEnum: string
{
    case MAKER = 'maker';
    case TAKER = 'taker';
    case BOTH = 'both';
    case PENDING = 'pending';

    const array TYPE_LABEL = [
        self::MAKER->value => 'Maker',
        self::TAKER->value => 'Taker',
        self::BOTH->value => 'Both',
        self::PENDING->value => 'Pending'
    ];

    const array TYPE_COLOR = [
        self::MAKER->value => 'primary',
        self::TAKER->value => 'info',
        self::BOTH->value => 'success',
        self::PENDING->value => 'warning'
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
