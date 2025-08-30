<?php

namespace App\Functions\FlashMessages;

use Illuminate\Support\Facades\Session;

class Toast
{
    public string $message = 'عملیات با موفقیت انجام شد';

    public string $color = 'linear-gradient(to right, #00b09b, #96c93d)';

    public string $gravity = 'top';

    public string $position = 'right';

    public int $duration = 3000;

    private function __construct() {}

    public static function message(?string $message = null): static
    {
        $toast = new static;
        $toast->message = is_null($message)
            ? $toast->message
            : $message;

        return $toast;
    }

    public function success(): static
    {
        $this->color = 'linear-gradient(to right, #96c93d, #96c93d)';

        return $this;
    }

    public function warning(): static
    {
        $this->color = 'linear-gradient(to right, #ffa514, #ffa514)';

        return $this;
    }

    public function danger(): static
    {
        $this->color = 'linear-gradient(to right, #ff4e08, #ff4e08)';

        return $this;
    }
    public function info(): static
    {
        $this->color = 'linear-gradient(to right, #3498db, #3498db)';

        return $this;
    }

    public function gravityTop(): static
    {
        $this->gravity = 'top';

        return $this;
    }

    public function gravityBottom(): static
    {
        $this->gravity = 'bottom';

        return $this;
    }

    public function positionLeft(): static
    {
        $this->position = 'left';

        return $this;
    }

    public function positionRight(): static
    {
        $this->position = 'right';

        return $this;
    }

    public function duration(int $second): static
    {
        $this->duration = $second * 1000;

        return $this;
    }

    public function notify(): void
    {
        Session::flash('toast', $this);
    }
}
