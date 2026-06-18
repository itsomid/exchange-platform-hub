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
