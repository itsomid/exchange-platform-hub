<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotBuyExecution extends Model
{
    public const UPDATED_AT = null;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_BUYING  = 'BUYING';
    public const STATUS_BOUGHT  = 'BOUGHT';
    public const STATUS_FAILED  = 'FAILED';
    public const STATUS_SKIPPED = 'SKIPPED';

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
        'exchange_order_id',
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
            'exchange_order_id' => 'string',
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

    public function sellOrders(): HasMany
    {
        return $this->hasMany(BotSellOrder::class);
    }
}
