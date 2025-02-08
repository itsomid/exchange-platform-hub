<?php

namespace App\Models;

use App\Helpers\Math;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $price
 * @property string $sell_price
 * @property string $buy_price
 * @property float  $exchange_profit_sell
 * @property float  $exchange_profit_buy
 */
class ExchangePrice extends Model
{
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function getSellPriceAttribute(): string
    {
        return Math::add(Math::mul($this->price , ( $this->exchange_profit_buy / 100 )) , $this->price);
    }

    public function getBuyPriceAttribute(): string
    {
        return Math::add(Math::mul($this->price , ( $this->exchange_profit_sell / 100 )) , $this->price);
    }
}
