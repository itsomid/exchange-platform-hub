<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getSetting(string $key): ?string
    {
        return static::query()->where('key', $key)->first()?->value;
    }
}
