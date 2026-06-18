<?php

namespace App\Models\Bot;

use App\Models\Currency;
use App\Models\ExchangeTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BotBuyExecution extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'bot_order_id',
        'currency_id',
        'signal_snapshot',
        'original_sell_orders_count',
        'effective_sell_orders_count',
        'allocated_usdt',
        'filled_amount',
        'avg_buy_price',
        'exchange_fee',
        'network_fee',
        'exchange_transaction_id',
        'status',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'signal_snapshot' => 'array',
            'allocated_usdt'  => 'decimal:8',
            'filled_amount'   => 'decimal:8',
            'avg_buy_price'   => 'decimal:8',
            'exchange_fee'    => 'decimal:8',
            'network_fee'     => 'decimal:8',
        ];
    }

    public function botOrder(): BelongsTo
    {
        return $this->belongsTo(BotOrder::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function exchangeTransaction(): BelongsTo
    {
        return $this->belongsTo(ExchangeTransaction::class);
    }

    public function sellOrders(): HasMany
    {
        return $this->hasMany(BotSellOrder::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(BotTradeSettlement::class);
    }

    public function scopeBought(Builder $query): Builder
    {
        return $query->where('status', 'BOUGHT');
    }
}
