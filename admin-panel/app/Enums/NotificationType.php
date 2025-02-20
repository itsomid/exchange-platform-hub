<?php

namespace App\Enums;

enum NotificationType :string
{
    case CoinexNotEnoughBalance = 'App\Notifications\CoinexNotEnoughBalance';
    case HotWalletNotEnoughBalance = 'App\Notifications\HotWalletNotEnoughBalance';

    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::CoinexNotEnoughBalance->value => 'عدم موجودی coinex',
            self::HotWalletNotEnoughBalance->value => 'عدم موجودی Hot Wallet',
            default => 'Unknown Problem',
        };
    }





}
