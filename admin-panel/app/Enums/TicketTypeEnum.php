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

    // Define the morph types for each ticket type
    /**
     * Get the model class associated with each enum case.
     */

    public function model(): string
    {
        return match ($this) {
            self::WITHDRAWAL => Withdrawal::class,
            self::DEPOSIT => Deposit::class,
            self::OTC_ORDER => OTCOrder::class,
        };
    }

    public static function getTypeClass(string $type): string
    {
        return match ($type) {
            self::WITHDRAWAL->value => Withdrawal::class,
            self::DEPOSIT->value => Deposit::class,
            self::OTC_ORDER->value => OTCOrder::class,
        };
    }
    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Find an enum case by value.
     */
    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
    // get type class
//    const array TYPE_LABEL = [
//        self::Withdrawal->value => 'برداشت',
//        self::Deposit->value => 'واریز',
//        self::OTC->value => 'معامله',
//    ];
//
//    const array TYPE_COLOR = [
//        self::Withdrawal->value => 'danger',
//        self::Deposit->value => 'success',
//        self::OTC->value => 'info',
//    ];

//    public function label(): string
//    {
//        return self::TYPE_LABEL[$this->value] ?? '';
//    }
//
//    /**
//     * Get color for the deposit status.
//     *
//     * @return string
//     */
//    public function color(): string
//    {
//        return self::TYPE_COLOR[$this->value] ?? '';
//    }

}
