<?php

namespace App\Models;

use App\Enums\SpotOrderSourceEnum;
use App\Enums\SpotOrderRoleEnum;
use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotRoleEnum;
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
 * @property OrderSourceEnum     $source
 * @property string              $quantity
 * @property string              $price
 * @property string              $filled_quantity
 * @property int                 $market_id
 * @property int                 $user_id
 * @property Market              $market
 */
class SpotOrder extends Model
{
    protected $appends = ['role'];

    protected $fillable = [
        'user_id',
        'market_id',
        'side',
        'type',
        'quantity',
        'price',
        'status',
        'filled_quantity',
        'source',
    ];

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

    public function botSetting(): BelongsTo
    {
        return $this->belongsTo(SpotBotSetting::class, 'bot_setting_id');
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

    public function getCommissionCurrency(): ?string
    {
        $role = $this->role;
        
        // اگر هنوز معامله‌ای انجام نشده، ارز کارمزد مشخص نیست
        if ($role === SpotOrderRoleEnum::PENDING) {
            return null;
        }
        
        // اگر هم maker و هم taker است، ارز کارمزد maker را برمی‌گردانیم
        if ($role === SpotOrderRoleEnum::BOTH || $role === SpotOrderRoleEnum::MAKER) {
            $commission = $this->makerCommissions()->first();
            return $commission?->maker_commission_currency;
        }
        
        // اگر فقط taker است
        if ($role === SpotOrderRoleEnum::TAKER) {
            $commission = $this->takerCommissions()->first();
            return $commission?->taker_commission_currency;
        }
        
        return null;
    }
    
}
