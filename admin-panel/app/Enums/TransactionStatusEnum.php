<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case FAILED = 'failed';
    case PENDING = 'pending';
    case SUCCESS = 'success';

    const array TYPE_LABEL = [
        self::PENDING->value => 'تکمیل نشده',
        self::SUCCESS->value => 'انجام شده',
        self::FAILED->value => 'ناموفق',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'warning',
        self::SUCCESS->value => 'success',
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
