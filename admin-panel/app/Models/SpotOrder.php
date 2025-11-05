<?php

namespace App\Models;

use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Helpers\Math;
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

    public function lockedBalanceDetails(): HasMany
    {
        return $this->hasMany(LockedBalanceDetail::class, 'spot_order_id');
    }

    public function getRemindedQuantity(): string
    {
        return Math::sub($this->quantity, $this->filled_quantity);
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

    /**
     * Calculate average price for market orders that filled multiple order book levels
     * This is used when price is null (market orders)
     */
    public function getAveragePrice(): ?string
    {
        // Only calculate for orders with null price (market orders)
        if ($this->price !== null) {
            return null;
        }

        // Get all trades related to this order (both as maker and taker)
        $allTrades = collect();
        
        // Add maker trades
        $allTrades = $allTrades->merge($this->makerTrades);
        
        // Add taker trades  
        $allTrades = $allTrades->merge($this->takerTrades);

        if ($allTrades->isEmpty()) {
            return null;
        }

        $totalValue = '0';
        $totalQuantity = '0';

        foreach ($allTrades as $trade) {
            $tradeValue = bcmul($trade->price, $trade->quantity, 8);
            $totalValue = bcadd($totalValue, $tradeValue, 8);
            $totalQuantity = bcadd($totalQuantity, $trade->quantity, 8);
        }

        if (bccomp($totalQuantity, '0', 8) === 0) {
            return null;
        }

        return bcdiv($totalValue, $totalQuantity, 8);
    }

    /**
     * Get display price - returns actual price or average price for market orders
     */
    public function getDisplayPrice(): ?string
    {
        if ($this->price !== null) {
            return $this->price;
        }

        return $this->getAveragePrice();
    }
}
