<?php

namespace App\Enums;

use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\Withdrawal;
use App\Models\StockContract;

enum TicketTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case STOCK = 'stock';
    case OTC_ORDER = 'otc_order';

    /**
     * Get the corresponding model class based on the enum value.
     */
    public function model(): string
    {
        return match ($this) {
            self::WITHDRAWAL => Withdrawal::class,
            self::DEPOSIT => Deposit::class,
            self::OTC_ORDER => OTCOrder::class,
            self::STOCK => StockContract::class,
        };
    }

    /**
     * Convert the model string (e.g., "deposit") to the corresponding enum value.
     */
    public static function fromString(string $ticketableType): ?self
    {
        return match ($ticketableType) {
            'withdrawal' => self::WITHDRAWAL,
            'deposit' => self::DEPOSIT,
            'otc_order' => self::OTC_ORDER,
            'stock' => self::STOCK,
            default => null,
        };
    }

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
