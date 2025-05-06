<?php

namespace App\Enums;

enum SpotOrderStatusEnum: string
{
    case OPEN = 'open';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
    case PARTIALLY_FILLED_CANCELED = 'partially_filled_canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
