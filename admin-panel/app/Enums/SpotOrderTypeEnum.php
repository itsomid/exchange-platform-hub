<?php

namespace App\Enums;

enum SpotOrderTypeEnum: string
{
    case MARKET = 'market';
    case LIMIT = 'limit';

    const array TYPE_LABEL = [
        self::MARKET->value => 'بازار',
        self::LIMIT->value => 'تعیین قیمت'
    ];

    public function label() : string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }
}
