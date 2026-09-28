<?php

namespace App\Services\Exchanges\Asset;

use App\Models\Admin;
use App\Notifications\RefExchangeWithdrawalFailed;

class AdminNotification
{
    public static function sendRefExchangeWithdrawalFailed(string $exchangeName, string $currency, string $amount, string $errorMessage): void
    {
        Admin::query()->role(['super_admin', 'admin'])->get()->each(function ($admin) use ($exchangeName, $currency, $amount, $errorMessage) {
            $admin->notify(new RefExchangeWithdrawalFailed($exchangeName, $currency, $amount, $errorMessage));
        });
    }
}
