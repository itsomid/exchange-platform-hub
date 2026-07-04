<?php

namespace App\Models;

use App\Enums\RefExchangeSellStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property int    $id
 * @property string $quantity
 * @property string $price
 */
class SpotTrade extends Model
{
    protected $fillable = [
        'maker_order_id', 'taker_order_id', 'quantity', 'price', 'market_id', 'ref_exchange_sell_status',
    ];

    protected function casts(): array
    {
        return [
            'ref_exchange_sell_status' => RefExchangeSellStatusEnum::class,
        ];
    }

    public function makerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'maker_order_id');
    }

    public function takerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'taker_order_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(TradingCommission::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function refExchangeTransaction(): MorphOne
    {
        return $this->morphOne(ExchangeTransaction::class, 'orderable');
    }
}
