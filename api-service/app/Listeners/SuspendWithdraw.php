<?php

namespace App\Listeners;

use App\Enums\FinancialBlockReasonsEnum;
use App\Enums\FinancialBlockActionEnum;
use App\Services\User\DTO\FinancialBlock\SaveFinancialBlockRequestDTO;
use App\Services\User\FinancialBlockService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;

class SuspendWithdraw implements ShouldQueue
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
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        resolve(FinancialBlockService::class)
            ->saveOrUpdateState(
                resolve(SaveFinancialBlockRequestDTO::class)
                    ->setUserId($user->id)
                    ->setAction(FinancialBlockActionEnum::WITHDRAW)
                    ->setRestrictedUntil(now()->addDays(2))
                    ->setReason(FinancialBlockReasonsEnum::PASSWORD_CHANGED)
            );
    }
}
