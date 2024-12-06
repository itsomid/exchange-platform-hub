<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'min_trade_amount',
        'max_trade_amount',
        'price',
        'exchange_profit',
        'is_active'
    ];

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency', 'symbol');
    }

    // Define the relationship to the Currency model (quote currency)
    public function quoteCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'quote_currency', 'symbol');
    }

    public function getPriceChangePercentageAttribute(): ?float
    {
        if ($this->open_price > 0) {
            return (($this->price - $this->open_price) / $this->open_price) * 100;
        }
        return null;
    }

    public function getExchangePriceAttribute()
    {
        return ($this->price * $this->exchange_profit) + $this->price;
    }
}
