<?php

namespace App\Models;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class SpotTrade extends Model
{
    use Filterable, HasApiTokens, HasFactory;
    public $filterNameSpace = 'App\Filters\SpotTradeFilter';

    protected $appends = ['maker_side', 'taker_side'];


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

    public function getMakerSideAttribute()
    {
        return $this->makerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
    public function getTakerSideAttribute()
    {
        return $this->takerOrder->side === SpotOrderSideEnum::SELL ? 'SELL' : 'BUY';
    }
}
