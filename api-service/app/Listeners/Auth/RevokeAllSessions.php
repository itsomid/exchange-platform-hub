<?php

namespace App\Listeners\Auth;

use App\Models\User;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\RevokeAllSessionRequestDTO;
use Illuminate\Auth\Events\PasswordReset;

class RevokeAllSessions
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
        /** @var User $user */
        $user = $event->user; // Help PHPStan and PHPStorm infer the correct type
        $accessTokenService = resolve(AccesstokenService::class);
        $accessTokenService->revokeAllSession(
            resolve(RevokeAllSessionRequestDTO::class)
                ->setUser($user)
        );
    }
}
