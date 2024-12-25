<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    const REF_DEFAULT_HEADER = ['Accept' => 'application/json'];

    protected $fillable = ['key', 'value'];

    public static function REFAddress()
    {
        return cache()->has('setting.refAddress')
            ? cache()->get('setting.refAddress')
            : static::query()->where('key', 'ref_base_address')->first()->value;
    }

    public static function Kavenegar_key()
    {
        return cache()->has('setting.kavenegarKey')
            ? cache()->get('setting.kavenegarKey')
            : static::query()->where('key', 'kavenegar_key')->first()->value;
    }

    public static function ReworkServiceAddress()
    {
        return cache()->has('setting.rework.service.address')
            ? cache()->get('setting.rework.service.address')
            : static::query()->where('key', 'rework_service_address')->first()->value;
    }

    public static function getSetting(string $key): ?string
    {
        return static::query()->where('key', $key)->first()?->value;
    }

}
