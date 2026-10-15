<?php

namespace App\Models\Bot;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BotSellOrder extends Model
{
    use HasFactory;

    public const CANCEL_ADMIN          = 'admin_cancel';
    public const CANCEL_USER           = 'user_cancel';
    public const CANCEL_EXCHANGE_SYNC  = 'exchange_sync';
    public const CANCEL_PLACE_FAILED   = 'sell_place_failed';
    public const CANCEL_PLACE_ROLLBACK = 'sell_place_rollback';
    public const CANCEL_ADMIN_CURRENCY = 'admin_currency_cancel';
    public const CANCEL_MIGRATION_MERGED    = 'migration_merged';
    public const CANCEL_MIGRATION_BELOW_MIN = 'migration_below_min';

    public const EXCHANGE_COINEX  = 'coinex';
    public const EXCHANGE_BINANCE = 'binance';

    protected $fillable = [
        'bot_buy_execution_id',
        'exchange_order_id',
        'exchange',
        'previous_exchange_order_id',
        'target_type',
        'target_value',
        'share_percent',
        'amount_to_sell',
        'sell_ref_exchange_fee',
        'status',
        'cancel_reason',
        'bot_currency_cancellation_id',
        'filled_at',
        'migrated_at',
    ];

    protected function casts(): array
    {
        return [
            'target_value'          => 'decimal:8',
            'share_percent'         => 'decimal:2',
            'amount_to_sell'        => 'decimal:8',
            'sell_ref_exchange_fee' => 'decimal:8',
            'filled_at'             => 'datetime',
            'migrated_at'           => 'datetime',
        ];
    }

    public function buyExecution(): BelongsTo
    {
        return $this->belongsTo(BotBuyExecution::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(BotTradeSettlement::class);
    }

    public function currencyCancellation(): BelongsTo
    {
        return $this->belongsTo(BotCurrencyCancellation::class, 'bot_currency_cancellation_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'OPEN');
    }
}
