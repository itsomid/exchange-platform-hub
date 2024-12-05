<?php

namespace App\Listeners;

use App\Enums\UserFinancialBlockAction;
use App\Services\User\DTO\FinancialBlock\SaveFinancialBlockRequestDTO;
use App\Services\User\FinancialBlockService;
use Illuminate\Auth\Events\PasswordReset;

class SuspendWithdraw
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
                    ->setAction(UserFinancialBlockAction::WITHDRAW)
                    ->setRestrictedUntil(now()->addDay())
                    ->setReason('Change Password')
            );
    }
}
