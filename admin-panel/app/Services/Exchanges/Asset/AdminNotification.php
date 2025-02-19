<?php

namespace App\Services\Exchanges\Asset;

use App\Models\Admin;
use App\Notifications\CoinexWithdrawalProblem;

class AdminNotification
{
    public static function dispatchCoinexHasProblem(string $message, string $currency, string $amount): void
    {
        Admin::query()->role('super_admin', 'admin')->get()->each(function ($admin) use ($message, $currency, $amount) {
            $admin->notify(new CoinexWithdrawalProblem($message, $currency, $amount));
        });
    }
}
