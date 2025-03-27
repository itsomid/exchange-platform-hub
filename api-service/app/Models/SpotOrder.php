<?php

namespace App\Models;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Helpers\Math;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int                 $id
 * @property SpotOrderTypeEnum   $type
 * @property SpotOrderSideEnum   $side
 * @property SpotOrderStatusEnum $status
 * @property string              $quantity
 * @property string              $price
 * @property string              $filled_quantity
 * @property int                 $market_id
 * @property int                 $user_id
 */
class SpotOrder extends Model
{
    protected $fillable = [
        'user_id',
        'market_id',
        'side',
        'type',
        'quantity',
        'price',
        'status',
        'filled_quantity',
    ];

    protected function casts(): array
    {
        return [
            'side' => SpotOrderSideEnum::class,
            'type' => SpotOrderTypeEnum::class,
            'status' => SpotOrderStatusEnum::class,
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function makerTrades(): HasMany
    {
        return $this->hasMany(SpotTrade::class, 'maker_order_id');
    }

    public function takerTrades(): HasMany
    {
        return $this->hasMany(SpotTrade::class, 'taker_order_id');
    }

    public function makerCommissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            TradingCommission::class,
            SpotTrade::class,
            'maker_order_id',
            'spot_trade_id',
            'id',
            'id'
        );
    }

    public function takerCommissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            TradingCommission::class,
            SpotTrade::class,
            'taker_order_id',
            'spot_trade_id',
            'id',
            'id'
        );
    }

    public function getRemindedQuantity(): string
    {
        return Math::sub($this->quantity, $this->filled_quantity);
    }
}
