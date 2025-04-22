<?php

namespace App\Enums;

enum SpotOrderSideEnum: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    const TYPE_LABEL = [
        self::BUY->value => 'خرید',
        self::SELL->value => 'فروش'
    ];

    const TYPE_COLOR = [
      self::BUY->value => 'success',
      self::SELL->value => 'danger',
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
