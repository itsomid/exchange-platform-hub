<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSignal extends Model
{
    protected $fillable = [
        'currency_id',
        'priority',
        'floor_price',
        'ceiling_price',
        'min_buy_amount_usdt',
        'max_allocation_percent',
        'p2p_min_order_value_override',
        'sell_orders_count',
        'sell_mode',
        'sell_targets',
        'is_active',
        'activation_pending_at',
        'price_in_range',
    ];

    protected function casts(): array
    {
        return [
            'floor_price'                  => 'decimal:8',
            'ceiling_price'                => 'decimal:8',
            'min_buy_amount_usdt'          => 'decimal:8',
            'max_allocation_percent'       => 'decimal:2',
            'p2p_min_order_value_override' => 'decimal:8',
            'sell_targets'                 => 'array',
            'is_active'                    => 'boolean',
            'activation_pending_at'        => 'datetime',
            'price_in_range'               => 'boolean',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Effective P2P minimum order value (signal override falls back to global).
     */
    public function getEffectiveP2pMinOrderValueAttribute(): string
    {
        return (string) (
            $this->p2p_min_order_value_override
            ?? BotGlobalSettings::current()->p2p_min_order_value
            ?? '5.00000000'
        );
    }

    /**
     * Smallest USDT allocation that could still produce a buy on this signal:
     * max(min_buy_amount_usdt, order floor), where the order floor mirrors the
     * allocator's D14 pre-check — effective_p2p_min_order_value, multiplied by
     * sell_orders_count in the conservative 'multi' floor mode.
     *
     * Shared by BotBuyOrchestrator (to gate a trigger) and by
     * bot:scan-buy-triggers (to decide whom to wake up), so the amount a user
     * must hold to be dispatched is always the amount the buy cycle will
     * actually require.
     */
    public function effectiveMinBuyUsdt(?string $floorMode = null): string
    {
        $floorMode ??= (string) (BotGlobalSettings::current()->precheck_floor_mode ?? 'multi');

        $p2pMin     = (string) $this->effective_p2p_min_order_value;
        $orderFloor = $floorMode === 'single'
            ? $p2pMin
            : bcmul((string) (int) $this->sell_orders_count, $p2pMin, 8);

        $minBuy = (string) $this->min_buy_amount_usdt;

        return bccomp($minBuy, $orderFloor, 8) >= 0 ? $minBuy : $orderFloor;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEligibleForPrice(Builder $query, float $currentPrice): Builder
    {
        return $query->where('floor_price', '<=', $currentPrice)
                     ->where('ceiling_price', '>=', $currentPrice);
    }
}
