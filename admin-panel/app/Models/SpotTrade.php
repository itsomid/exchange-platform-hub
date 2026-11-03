<?php

namespace App\Models;

use App\Enums\RefExchangeSellStatusEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class SpotTrade extends Model
{
    use Filterable, HasApiTokens, HasFactory, SoftDeletes;
    public $filterNameSpace = 'App\Filters\SpotTradeFilter';

    protected $fillable = ['notes', 'ref_exchange_sell_status'];

    protected $appends = ['maker_side', 'taker_side'];

    protected function casts(): array
    {
        return [
            'ref_exchange_sell_status' => RefExchangeSellStatusEnum::class,
        ];
    }


    public function market()
    {
        return $this->belongsTo(Market::class);
    }
    public function makerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'maker_order_id');
    }

    public function takerOrder(): BelongsTo
    {
        return $this->belongsTo(SpotOrder::class, 'taker_order_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(TradingCommission::class);
    }

    public function refExchangeTransaction(): MorphOne
    {
        return $this->morphOne(ExchangeTransaction::class, 'orderable');
    }

    /**
     * Reference-exchange hedge for a bot trade.
     * Bot bought the coin → sell it on the reference exchange.
     * Bot sold the coin → buy it back on the reference exchange.
     */
    public function refExchangeHedgeSide(): string
    {
        foreach ([$this->makerOrder, $this->takerOrder] as $order) {
            if ($order?->source === SpotOrderSourceEnum::BOT && $order->side === SpotOrderSideEnum::BUY) {
                return 'sell';
            }
        }

        foreach ([$this->makerOrder, $this->takerOrder] as $order) {
            if ($order?->source === SpotOrderSourceEnum::BOT && $order->side === SpotOrderSideEnum::SELL) {
                return 'buy';
            }
        }

        return 'sell';
    }

    public function refExchangeDisplayName(?string $fallback = null): ?string
    {
        return $this->refExchangeTransaction?->exchange?->name
            ?? $this->market?->activeExchangePrice?->exchange?->name
            ?? $fallback;
    }

    public function getMakerSideAttribute()
    {
        return $this->makerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
    public function getTakerSideAttribute()
    {
        return $this->takerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
}
