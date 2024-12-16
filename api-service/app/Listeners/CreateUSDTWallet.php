<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class CreateUSDTWallet
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        $user->wallets()->updateOrCreate([
            'currency_symbol' => 'USDT',
        ], [
            'balance' => 0,
            'locked_balance' => 0,
        ]);
    }
}
