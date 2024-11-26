<?php

namespace App\Services\Profile;

class UserUpdateProfileRequestDTO
{
    private int $userId;

    private string $firstName;

    private string $lastName;

    private string $mobile;

    public function setFirstName(string $firstName): UserUpdateProfileRequestDTO
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): UserUpdateProfileRequestDTO
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setMobile(string $mobile): UserUpdateProfileRequestDTO
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
