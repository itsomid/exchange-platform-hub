<?php

namespace App\Enums;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REJECTED = 'rejected';

    const array TYPE_LABEL = [
        self::PENDING->value => 'تکمیل نشده',
        self::AWAITING_APPROVAL->value => 'در انتظار تأیید',
        self::QUEUED->value => 'در صف ارسال',
        self::PROCESSING->value => 'در حال پردازش',
        self::COMPLETED->value => 'انجام شده',
        self::FAILED->value => 'ناموفق',
        self::REJECTED->value => 'رد شده',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::AWAITING_APPROVAL->value => 'info',
        self::QUEUED->value => 'warning',
        self::PROCESSING->value => 'info',
        self::COMPLETED->value => 'success',
        self::FAILED->value => 'danger',
        self::REJECTED->value => 'danger',
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
