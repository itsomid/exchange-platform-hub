<?php

namespace App\Enums;

enum NotificationType :string
{
    case CoinexNotEnoughBalance = 'App\Notifications\CoinexNotEnoughBalance';

    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::CoinexNotEnoughBalance->value => 'عدم موجودی coinex',
            default => 'Unknown Notification',
        };
    }





}
