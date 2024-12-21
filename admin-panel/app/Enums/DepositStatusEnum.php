<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    const array TYPE_LABEL = [
        self::PENDING->value => 'pending',
        self::COMPLETED->value => 'completed',
        self::FAILED->value => 'failed',
    ];

    const array TYPE_COLOR = [
        self::PENDING->value => 'primary',
        self::COMPLETED->value => 'warning',
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
