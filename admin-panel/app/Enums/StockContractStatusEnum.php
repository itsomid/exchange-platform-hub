<?php

namespace App\Enums;

enum StockContractStatusEnum: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case SOLD = 'sold';

    const array TYPE_LABEL = [
        self::ACTIVE->value => 'فعال',
        self::CANCELED->value => 'لغو شده',
        self::SOLD->value => 'فروخته شده'
    ];

    const array TYPE_COLOR = [
        self::ACTIVE->value => 'success',
        self::CANCELED->value => 'danger',
        self::SOLD->value => 'warning'
    ];


    public function label() : string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }
    public function color():string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
