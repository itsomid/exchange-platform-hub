<?php

namespace App\Enums;

enum RefExchangeSellStatusEnum: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'نیاز نیست',
            self::PENDING => 'در انتظار',
            self::COMPLETED => 'تکمیل شده',
            self::FAILED => 'ناموفق',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'secondary',
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
}
