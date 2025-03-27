<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property string $quantity
 */
class SpotTrade extends Model
{
    protected $fillable = [
        'maker_order_id', 'taker_order_id', 'quantity', 'price', 'market_id',
    ];

    public function makerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'id', 'marker_order_id');
    }

    public function takerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'id', 'taker_order_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(TradingCommission::class);
    }
}
