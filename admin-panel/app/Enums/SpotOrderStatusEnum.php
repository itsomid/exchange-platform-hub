<?php

namespace App\Enums;

enum SpotOrderStatusEnum: string
{
    case OPEN = 'open';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    const array TYPE_LABEL = [
        self::OPEN->value => 'باز',
        self::COMPLETED->value => 'کامل شده',
        self::CANCELED->value => 'لغو شده'
    ];

    const array TYPE_COLOR = [
        self::OPEN->value => 'warning',
        self::COMPLETED->value => 'success',
        self::CANCELED->value => 'danger'
    ];

    public function label() : string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    public function color() : string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
