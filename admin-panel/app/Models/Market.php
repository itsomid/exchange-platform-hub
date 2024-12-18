<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Market extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'min_trade_amount',
        'max_trade_amount',
        'price',
        'exchange_profit',
        'exchange_id',
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
    public function activeExchange()
    {
        return $this->hasOneThrough(Exchange::class, ExchangePrice::class, 'market_id', 'id', 'id', 'exchange_id');
    }

    public function activeExchangePrice(): HasOne
    {
        return $this->hasOne(ExchangePrice::class);
    }


}
