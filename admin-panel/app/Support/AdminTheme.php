<?php

namespace App\Support;

class AdminTheme
{
    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const SYSTEM = 'system';

    public static function preference(): string
    {
        $theme = session('theme', self::LIGHT);

        return in_array($theme, [self::LIGHT, self::DARK, self::SYSTEM], true)
            ? $theme
            : self::LIGHT;
    }
}
