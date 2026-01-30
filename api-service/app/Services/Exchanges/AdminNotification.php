<?php

namespace App\Services\Exchanges;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\CoinexPriceDifferenceTooLarge;
use App\Notifications\CoinexHasError;
use App\Notifications\CoinexSpotTradingIsTooSmall;
use App\Notifications\HotWalletNotEnoughBalance;

class AdminNotification
{
    public static function sendHotWalletNotEnoughBalance(string $currencyName, string $amount, User $user): void
    {
        Admin::query()->role('super_admin')->get()->unique('id')->each(function ($admin) use ($currencyName, $amount, $user) {
            $admin->notify(new HotWalletNotEnoughBalance($currencyName, $amount, $user));
        });
    }

    public static function sendSpotTradingIsTooSmall(string $marketName, string $amount): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->unique('id')->each(function ($admin) use ($marketName, $amount) {
            $admin->notify(new CoinexSpotTradingIsTooSmall($marketName, $amount));
        });
    }

    public static function sendPriceDifferenceTooLarge(string $marketName, string $amount, string $message): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->unique('id')->each(function ($admin) use ($marketName, $amount,$message) {
            $admin->notify(new CoinexPriceDifferenceTooLarge($marketName, $amount,$message));
        });
    }
    public static function logError(string $marketName, string $amount, string $errorMessage): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->unique('id')->each(function ($admin) use ($marketName, $amount, $errorMessage) {
            $admin->notify(new CoinexHasError($marketName, $amount, $errorMessage));
        });
    }

    public static function sendRefExchangeNotEnoughBalance(string $exchangeName, string $marketName, string $amount, string $orderType = 'sell'): void
    {
        Admin::query()->role(['tech_developers'])->get()->unique('id')->each(function ($admin) use ($exchangeName, $marketName, $amount, $orderType) {
            $admin->notify(new \App\Notifications\RefExchangeNotEnoughBalance($exchangeName, $marketName, $amount, $orderType));
        });
    }
}
