<?php

namespace App\Repositories\Auth;

use App\Models\User;

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
                    'first_name' => $registerRequestDTO->getFirstName(),
                    'username' => $registerRequestDTO->getUsername(),
                    'last_name' => $registerRequestDTO->getLastName(),
                    'password' => $registerRequestDTO->getHashedPassword(),
                    'introducer_code' => $registerRequestDTO->getIntroducerId(),
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
                'email_verified_at' => now(),
            ]);
    }

    public function getUserByEmail(string $email): User
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
}
