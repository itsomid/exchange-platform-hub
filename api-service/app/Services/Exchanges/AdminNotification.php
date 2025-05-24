<?php

namespace App\Services\Exchanges;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\CoinexHasError;
use App\Notifications\CoinexNotEnoughBalance;
use App\Notifications\CoinexSpotTradingIsTooSmall;
use App\Notifications\HotWalletNotEnoughBalance;

class AdminNotification
{
    public static function sendEnoughBalance(string $marketName, string $amount): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->each(function ($admin) use ($marketName, $amount) {
            $admin->notify(new CoinexNotEnoughBalance($marketName, $amount));
        });
    }

    public static function sendHotWalletNotEnoughBalance(string $currencyName, string $amount, User $user): void
    {
        Admin::query()->role('super_admin')->get()->each(function ($admin) use ($currencyName, $amount, $user) {
            $admin->notify(new HotWalletNotEnoughBalance($currencyName, $amount, $user));
        });
    }

    public static function sendSpotTradingIsTooSmall(string $marketName, string $amount): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->each(function ($admin) use ($marketName, $amount) {
            $admin->notify(new CoinexSpotTradingIsTooSmall($marketName, $amount));
        });
    }

    public static function logError(string $marketName, string $amount, string $errorMessage): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->each(function ($admin) use ($marketName, $amount, $errorMessage) {
            $admin->notify(new CoinexHasError($marketName, $amount, $errorMessage));
        });
    }
}
