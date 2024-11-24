<?php

namespace App\Repositories\Auth;

use App\Models\User;

interface UserRepositoryInterface
{
    public function registerOrUpdate(UserRegisterRequestDTO $registerRequestDTO): User;

    public function isUserExistsByEmail(string $email): bool;

    public function activeAccount(int $userId): void;

    public function getUserByEmail(string $email): User;

    public function getUserById(int $id): User;

    public function isUsernameExists(string $username): bool;

    public function updateLastLogin(UpdateLastLoginRequestDTO $lastLoginDTO): void;
}
