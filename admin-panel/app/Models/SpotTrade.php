<?php

namespace App\Models;

use App\Enums\RefExchangeSellStatusEnum;
use App\Enums\SpotOrderSideEnum;
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

    public function getMakerSideAttribute()
    {
        return $this->makerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
    public function getTakerSideAttribute()
    {
        return $this->takerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
}
