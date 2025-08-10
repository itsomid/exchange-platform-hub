<?php

namespace App\Models;

use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SpotOrder extends Model
{
    use HasFactory;
    public $filterNameSpace = 'App\Filters\SpotOrderFilter';
    protected $appends = ['role'];
    protected function casts(): array
    {
        return [
            'side' => SpotOrderSideEnum::class,
            'type' => SpotOrderTypeEnum::class,
            'status' => SpotOrderStatusEnum::class,
            'source' => SpotOrderSourceEnum::class,
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

    public function getFilledValue(): string
    {
        $filledValue = $this->makerTrades->reduce(function (string $carry, SpotTrade $item) {
            return Math::add(
                $carry,
                Math::mul(
                    $item->price,
                    $item->quantity
                )
            );
        }, 0);

        return $this->takerTrades->reduce(function (string $carry, SpotTrade $item) {
            return Math::add(
                $carry,
                Math::mul(
                    $item->price,
                    $item->quantity
                )
            );
        }, $filledValue);
    }
    

    public function getRoleAttribute(): SpotOrderRoleEnum
    {
        if ($this->type === SpotOrderTypeEnum::MARKET) {
            return SpotOrderRoleEnum::TAKER;
        }

        if ($this->makerTrades()->exists()) {
            return $this->takerTrades()->exists() ? SpotOrderRoleEnum::BOTH : SpotOrderRoleEnum::MAKER;
        }

        return $this->takerTrades()->exists() ? SpotOrderRoleEnum::TAKER : SpotOrderRoleEnum::PENDING;
    }

    public function isMaker(): bool
    {
        if ($this->type === SpotOrderTypeEnum::MARKET) {
            return false;
        }

        return $this->makerTrades()->exists();
    }

    public function isTaker(): bool
    {

        if ($this->type === SpotOrderTypeEnum::MARKET) {
            return true;
        }
        return $this->takerTrades()->exists();
    }
}
