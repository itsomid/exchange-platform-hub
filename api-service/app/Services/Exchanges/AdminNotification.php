<?php

namespace App\Services\Exchanges;

use App\Models\Admin;
use App\Notifications\CoinexNotEnoughBalance;

class AdminNotification
{
    public static function sendEnoughBalance(string $marketName, string $amount): void
    {
        Admin::query()->role('super_admin')->get()->each(function ($admin) use ($marketName, $amount) {
            $admin->notify(new CoinexNotEnoughBalance($marketName, $amount));
        });
    }
}
