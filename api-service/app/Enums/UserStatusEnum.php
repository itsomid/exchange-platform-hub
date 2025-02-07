<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPEND = 'suspended';
    case INACTIVE = 'inactive';

    const array TYPE_LABEL = [
        self::ACTIVE->value => 'فعال',
        self::SUSPEND->value => 'معلق',
        self::INACTIVE->value => 'غیرفعال',
    ];

    const array TYPE_COLOR = [
        self::ACTIVE->value => 'success',
        self::SUSPEND->value => 'danger',
        self::INACTIVE->value => 'secondary',
    ];

    /**
     * Get label for the deposit status.
     */
    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
