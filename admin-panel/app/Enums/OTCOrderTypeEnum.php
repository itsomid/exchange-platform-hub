<?php

namespace App\Enums;

enum OTCOrderTypeEnum :string
{
    case BUY = 'buy';
    case SELL = 'sell';


    const array TYPE_LABEL = [
        self::BUY->value => 'خرید',
        self::SELL->value => 'فروش',
    ];

    const array TYPE_COLOR = [
        self::BUY->value => 'success',
        self::SELL->value => 'danger',

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
