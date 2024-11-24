<?php

namespace App\Services\Auth\DTO;

use App\Models\User;

class LoginResponseDTO
{
    private User $user;

    private bool $hasGoogle2fa;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setHasGoogle2fa(bool $hasGoogle2fa): self
    {
        $this->hasGoogle2fa = $hasGoogle2fa;

        return $this;
    }

    public function getHasGoogle2fa(): bool
    {
        return $this->hasGoogle2fa;
    }
}
