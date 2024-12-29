<?php

namespace App\Models;

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

    public function getSellPriceAttribute(): string
    {
        return bcmul($this->price, (string) ($this->exchange_profit_sell + 1), 8);
    }
    public function getBuyPriceAttribute(): string
    {
        return bcmul($this->price, (string) ($this->exchange_profit_sell + 1), 8);
    }
}
