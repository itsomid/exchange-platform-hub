<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\DTO\User\UpdateLastLoginRequestDTO;
use App\Repositories\DTO\User\UserRegisterRequestDTO;

interface UserRepositoryInterface
{
    public function registerOrUpdate(UserRegisterRequestDTO $registerRequestDTO): User;

    public function isUserExistsByEmail(string $email): bool;

    public function activeAccount(int $userId): void;

    public function getUserByEmail(string $email): User;

    public function getUserById(int $id): User;

    public function isUsernameExists(string $username): bool;

    public function updateLastLogin(UpdateLastLoginRequestDTO $lastLoginDTO): void;

    public function saveSecret(int $userId, string $secret): void;

    public function updateUser(int $userId, array $data): void;
}
