<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case TOO_SMALL = 'too_small';

    const array TYPE_LABEL = [
        self::PENDING->value => 'تکمیل نشده',
        self::CONFIRMED->value => 'تایید شده',
        self::FAILED->value => 'ناموفق',
        self::TOO_SMALL->value => 'کمتر از مقدار مجاز',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::CONFIRMED->value => 'success',
        self::FAILED->value => 'danger',
        self::TOO_SMALL->value => 'warning',
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
