<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string        $base_currency
 * @property string        $quote_currency
 * @property bool          $is_active
 * @property ExchangePrice $exchangePrice
 * @property string        $min_trade_amount
 * @property string        $max_trade_amount
 * @property int           $id
 * @property string        $market_name
 * @property string        $min_otc_amount
 * @property string        $max_otc_amount
 * @property Currency      $currency
 */
class Market extends Model
{
    protected $fillable = [
        'price',
    ];

    public function exchangePrice(): HasOne
    {
        return $this->hasOne(ExchangePrice::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency', 'symbol');
    }

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency', 'symbol');
    }

    public function quoteCurrency()
    {
        return $this->belongsTo(Currency::class, 'quote_currency', 'symbol');
    }
    public function getMarketNameAttribute(): string
    {
        return $this->base_currency . '' . $this->quote_currency;
    }
}
