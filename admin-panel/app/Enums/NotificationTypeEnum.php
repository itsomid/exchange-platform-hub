<?php

namespace App\Enums;

enum NotificationTypeEnum :string
{
    case RefExchangeNotEnoughBalance = 'App\Notifications\RefExchangeNotEnoughBalance';
    case HotWalletNotEnoughBalance = 'App\Notifications\HotWalletNotEnoughBalance';
    case WithdrawalFailed = 'App\Notifications\WithdrawalFailed';
    case RefExchangeWithdrawalFailed = 'App\Notifications\RefExchangeWithdrawalFailed';
    case RefExchangeOrderFailed = 'App\Notifications\RefExchangeOrderFailed';

    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::RefExchangeNotEnoughBalance->value => 'عدم موجودی صرافی مرجع',
            self::HotWalletNotEnoughBalance->value => 'عدم موجودی Hot Wallet',
            self::WithdrawalFailed->value => 'خطا در برداشت',
            self::RefExchangeWithdrawalFailed->value => 'مشکل در برداشت از صرافی مرجع',
            self::RefExchangeOrderFailed->value => 'مشکل در معامله صرافی مرجع',
            default => 'Unknown Problem',
        };
    }




}
