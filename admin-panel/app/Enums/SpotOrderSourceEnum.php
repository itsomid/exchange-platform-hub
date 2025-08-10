<?php

namespace App\Enums;

enum SpotOrderSourceEnum: string
{
    case USER = 'user';
    case BOT = 'bot';
    case API = 'api';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::USER => 'کاربر',
            self::BOT => 'ربات معاملاتی',
            self::API => 'API خارجی',
            self::ADMIN => 'مدیر سیستم',
        };
    }

    public function isBot(): bool
    {
        return $this === self::BOT;
    }

    public function isManual(): bool
    {
        return in_array($this, [self::USER, self::ADMIN]);
    }
}
