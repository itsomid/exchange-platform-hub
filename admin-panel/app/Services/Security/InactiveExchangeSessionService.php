<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class InactiveExchangeSessionService
{
    public const IDLE_MINUTES = 15;

    /**
     * Tokens minted by the panel itself, not by a user login.
     * A fresh one of these must not mark an idle user as online.
     */
    private const IGNORED_FRESH_TOKEN_NAMES = [
        'system',
        'stock_pdf',
        'login_as_user',
    ];

    public function summary(): array
    {
        return [
            'idle_minutes' => self::IDLE_MINUTES,
            'active_users' => $this->activeTokens()->distinct()->count('tokenable_id'),
            'kept_tokens' => $this->activeTokens()->count(),
            'idle_users' => $this->revocableTokens()->distinct()->count('tokenable_id'),
            'revocable_tokens' => $this->revocableTokens()->count(),
        ];
    }

    /**
     * Expire and delete every exchange-user token that belongs to someone
     * with no activity in the idle window. Online users and admin tokens stay.
     *
     * @return array{deleted_tokens: int, affected_users: int}
     */
    public function revoke(): array
    {
        $affectedUsers = $this->revocableTokens()->distinct()->count('tokenable_id');
        $deleted = 0;

        $this->revocableTokens()->orderBy('id')->chunkById(500, function ($tokens) use (&$deleted) {
            $deleted += PersonalAccessToken::query()->whereKey($tokens->modelKeys())->delete();
        });

        return [
            'deleted_tokens' => $deleted,
            'affected_users' => $affectedUsers,
        ];
    }

    private function userTokens(): Builder
    {
        return PersonalAccessToken::query()->where('tokenable_type', (new User)->getMorphClass());
    }

    private function activeTokens(): Builder
    {
        return $this->userTokens()->whereIn('tokenable_id', $this->activeUserIdSubquery());
    }

    private function revocableTokens(): Builder
    {
        return $this->userTokens()->whereNotIn('tokenable_id', $this->activeUserIdSubquery());
    }

    private function activeUserIdSubquery(): Builder
    {
        $cutoff = now()->subMinutes(self::IDLE_MINUTES);

        return PersonalAccessToken::query()
            ->select('tokenable_id')
            ->where('tokenable_type', (new User)->getMorphClass())
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('last_used_at', '>=', $cutoff)
                    ->orWhere(function (Builder $query) use ($cutoff) {
                        $query->whereNull('last_used_at')
                            ->where('created_at', '>=', $cutoff)
                            ->whereNotIn('name', self::IGNORED_FRESH_TOKEN_NAMES);
                    });
            });
    }
}
