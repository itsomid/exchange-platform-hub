<?php

namespace App\Enums;

enum StockTypeEnum: string
{
    case NORMAL = 'normal';
    case GIFT = 'gift';
    case PARTNER = 'partner';

    const array TYPE_LABEL = [
        self::NORMAL->value => 'سهام عادی',
        self::GIFT->value => 'سهام هدیه',
        self::PARTNER->value => 'سهام همکار',
    ];

    public function label() : string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }
} 