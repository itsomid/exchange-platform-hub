<?php

namespace App\Listeners;

use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

class CalculateUserBalanceListener
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
    public function handle(object $event): void
    {

        $user = User::query()->find($event->order->user_id);

        // Calculate the sum of the balance
        $sumOfBuy = Transaction::query()->where('user_id', $user->id)
            ->where('transactionType', TransactionTypeEnum::BUY)
            ->sum('balance');

        $account = Account::query()
            ->firstOrCreate(['user_id' => $user->id], [
                'cash_balance' => 0,
                'gift_balance' => 0,
            ]);

        $account->cash_balance -= $sumOfBuy;
        $account->save();

    }
}
