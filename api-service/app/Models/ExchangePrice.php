<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $price
 * @property float  $exchange_profit_sell
 * @property float  $exchange_profit_buy
 */
class ExchangePrice extends Model
{
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
