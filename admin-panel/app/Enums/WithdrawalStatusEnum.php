<?php

namespace App\Enums;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    const array TYPE_LABEL = [
        self::PENDING->value => 'تکمیل نشده',
        self::AWAITING_APPROVAL->value => 'در انتظار تأیید',
        self::COMPLETED->value => 'انجام شده',
        self::FAILED->value => 'ناموفق',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::AWAITING_APPROVAL->value => 'info',
        self::COMPLETED->value => 'success',
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
