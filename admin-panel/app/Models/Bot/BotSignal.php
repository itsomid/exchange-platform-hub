<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Bot\BotGlobalSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotSignal extends Model
{
    use HasFactory;

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

    /**
     * Returns the effective P2P minimum order value for this signal.
     * Uses the signal-level override when set; falls back to the global setting.
     */
    public function getEffectiveP2pMinOrderValueAttribute(): string
    {
        return $this->p2p_min_order_value_override
            ?? BotGlobalSettings::current()->p2p_min_order_value
            ?? '5.00000000';
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function buyExecutions(): HasMany
    {
        return $this->hasMany(BotBuyExecution::class, 'currency_id', 'currency_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Signals where floor_price <= $currentPrice <= ceiling_price.
     */
    public function scopeEligibleForPrice(Builder $query, float $currentPrice): Builder
    {
        return $query->where('floor_price', '<=', $currentPrice)
                     ->where('ceiling_price', '>=', $currentPrice);
    }
}
