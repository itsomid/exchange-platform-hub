<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';

    const array TYPE_LABEL = [
        self::PENDING->value => 'تکمیل نشده',
        self::CONFIRMED->value => 'تایید شده',
        self::FAILED->value => 'ناموفق',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::CONFIRMED->value => 'success',
        self::FAILED->value => 'danger',
    ];

    /**
     * Get label for the deposit status.
     *
     * @return string
     */
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
