<?php

namespace App\Enums;

enum OTCOrderStatusEnum: string
{
    case SUCCESS = 'success';
    case CANCELED = 'canceled';
    case PENDING = 'pending';

    const array TYPE_LABEL = [
        self::SUCCESS->value => 'موفق',
        self::CANCELED->value => 'لغو شده',
        self::PENDING->value => 'در انتظار',
    ];

    const array TYPE_COLOR = [
        self::SUCCESS->value => 'success',
        self::CANCELED->value => 'danger',
        self::PENDING->value => 'warning',
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
