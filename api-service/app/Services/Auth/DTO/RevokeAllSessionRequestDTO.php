<?php

namespace App\Services\Auth\DTO;

use App\Models\User;

class RevokeAllSessionRequestDTO
{
    private User $user;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
