<?php

namespace App\Enums;

enum OTCRefExchangeWithdrawalStatusEnum: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case CANCELLED = 'CANCELLED';

    const array TYPE_LABEL = [
        self::PENDING->value => 'در انتظار تکمیل',
        self::COMPLETED->value => 'تکمیل شده',
        self::CANCELLED->value => 'لغو شده',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::COMPLETED->value => 'success',
        self::CANCELLED->value => 'danger',
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
