<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;

enum TicketTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case OTC_ORDER = 'otc_order';

    case UNKNOWN = 'unknown';

    /**
     * Get the corresponding model class for each type.
     */

    public static function fromModelClass(string $modelClass): ?self
    {
        $classToEnumMap = [
            Withdrawal::class => self::WITHDRAWAL,
            Deposit::class => self::DEPOSIT,
            OTCOrder::class => self::OTC_ORDER,
        ];

        return $classToEnumMap[$modelClass] ?? self::UNKNOWN;
    }

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // get type class
    // Mapping arrays for labels and colors
    const array TYPE_LABEL = [
        self::WITHDRAWAL->value => 'برداشت',
        self::DEPOSIT->value => 'واریز',
        self::OTC_ORDER->value => 'معامله',
        self::UNKNOWN->value => 'نامشخص',
    ];

    const array TYPE_COLOR = [
        self::WITHDRAWAL->value => 'danger',
        self::DEPOSIT->value => 'success',
        self::OTC_ORDER->value => 'info',
        self::UNKNOWN->value => 'secondary',

    ];

    /**
     * Get the corresponding label for the enum value.
     */
    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get the corresponding color for the enum value.
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }

}
