<?php

namespace App\Enums;

enum NotificationTypeEnum :string
{
    case CoinexNotEnoughBalance = 'App\Notifications\CoinexNotEnoughBalance';
    case HotWalletNotEnoughBalance = 'App\Notifications\HotWalletNotEnoughBalance';
    case CoinexWithdrawalProblem = 'App\Notifications\CoinexWithdrawalProblem';
    case CoinexSpotTradingIsTooSmall = 'App\Notifications\CoinexSpotTradingIsTooSmall';
    case CoinexProblem = 'App\Notifications\CoinexHasError';

    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::CoinexNotEnoughBalance->value => 'عدم موجودی coinex',
            self::HotWalletNotEnoughBalance->value => 'عدم موجودی Hot Wallet',
            self::CoinexWithdrawalProblem->value => 'مشکل در برداشت coinex',
            self::CoinexSpotTradingIsTooSmall->value => 'مشکل در معامله coinex',
            self::CoinexProblem->value => 'مشکل در coinex',
            default => 'Unknown Problem',
        };
    }





}
