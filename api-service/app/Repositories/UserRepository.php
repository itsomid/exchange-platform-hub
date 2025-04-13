<?php

namespace App\Repositories;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Repositories\DTO\User\UpdateLastLoginRequestDTO;
use App\Repositories\DTO\User\UserRegisterRequestDTO;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserRepository implements UserRepositoryInterface
{
    /** if user exists by his email then updated he otherwise created he
     */
    public function registerOrUpdate(UserRegisterRequestDTO $registerRequestDTO): User
    {
        return User::query()
            ->updateOrCreate(
                ['email' => $registerRequestDTO->getEmail()],
                [
                    'first_name' => null,
                    'username' => $registerRequestDTO->getUsername(),
                    'last_name' => null,
                    'password' => $registerRequestDTO->getHashedPassword(),
                    'introducer_code' => $registerRequestDTO->getIntroducerId(),
                    'registration_date' => $registerRequestDTO->getRegistrationDate(),
                    'status' => $registerRequestDTO->getUserStatus(),
                ]
            );
    }

    /**
     * Check User Existent
     */
    public function isUserExistsByEmail(string $email): bool
    {
        return User::query()
            ->where('email', $email)
            ->exists();
    }

    public function activeAccount(int $userId): void
    {
        User::query()
            ->where('id', $userId)
            ->update([
                'status' => UserStatusEnum::ACTIVE,
                'email_verified_at' => now(),
            ]);
    }

    public function getUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function isUsernameExists(string $username): bool
    {
        return User::query()->where('username', $username)->exists();
    }

    public function getUserById(int $id): User
    {
        return User::query()->find($id);
    }

    public function updateLastLogin(UpdateLastLoginRequestDTO $lastLoginDTO): void
    {
        User::query()
            ->where('id', $lastLoginDTO->getId())
            ->update([
                'last_login' => $lastLoginDTO->getLastLogin(),
                'last_ip_address' => $lastLoginDTO->getIpAddress(),
            ]);
    }

    public function saveSecret(int $userId, string $secret): void
    {
        User::query()
            ->where('id', $userId)
            ->update([
                'two_factor_secret' => $secret,
            ]);
    }

    public function updateUser(int $userId, array $data): void
    {
        User::query()
            ->where('id', $userId)
            ->update($data);
    }

    public function getReferredUsers(int $referralId): Collection
    {
        return Cache::remember(__CLASS__.'.getReferredUsers.'.$referralId, now()->addHours(1), fn () => User::query()
            ->where('introducer_code', $referralId)
            ->withCount('referredTransactions')
            ->withSum('referredTransactions', 'amount')
            ->get());
    }
}
