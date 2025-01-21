<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function getMarketNameAttribute(): string
    {
        return $this->base_currency.''.$this->quote_currency;
    }
}
