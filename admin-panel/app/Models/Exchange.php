<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    public function exchangePrices()
    {
        return $this->hasMany(ExchangePrice::class);
    }

    // The relationship between Exchange and Market through ExchangePrice
    public function markets()
    {
        return $this->belongsToMany(Market::class, 'exchange_prices');
    }
    // Scope to get only the active exchange
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    // Method to set an exchange as active and deactivate the others
    public static function setActiveExchange(Exchange $exchange)
    {
        // Deactivate all exchanges
        self::query()->update(['is_active' => false]);

        // Set the selected exchange as active
        $exchange->is_active = true;
        $exchange->save();
    }
}
