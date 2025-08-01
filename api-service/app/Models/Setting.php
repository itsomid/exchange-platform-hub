<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    protected $casts = [
        'value' => 'json',
    ];

    public static function getSetting(string $key, $default = null)
    {
        $setting = static::query()->where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => is_string($setting->value) ? json_decode($setting->value, true) : $setting->value,
            default => $setting->value,
        };
    }

    public static function isEnabled(string $key): bool
    {
        return static::getSetting($key, false);
    }

}
