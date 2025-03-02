<?php

namespace App\Enums;

use App\Models\Admin;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\User;
use App\Models\Withdrawal;

enum TicketRepliableTypeEnum :string
{
    case USER = 'user';
    case ADMIN = 'admin';

    public static function fromModelClass(string $modelClass): ?self
    {
        $classToEnumMap = [
            User::class => self::USER,
            Admin::class => self::ADMIN,

        ];

        return $classToEnumMap[$modelClass] ?? null;
    }
    /**
     * Get all possible values of the enum.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the repliable type.
     *
     * @return string
     */
    const array TYPE_LABEL = [
        self::USER->value => 'کاربر',
        self::ADMIN->value => 'مدیر',
    ];

    const array TYPE_COLOR = [
        self::USER->value => 'danger',
        self::ADMIN->value => 'primary',

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
