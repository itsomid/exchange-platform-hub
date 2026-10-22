<?php

namespace App\Models\Bot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSellOrder extends Model
{
    public const STATUS_OPEN     = 'OPEN';
    public const STATUS_FILLED   = 'FILLED';
    public const STATUS_CANCELED = 'CANCELED';

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

    public const PLACEMENT_PLACED  = 'PLACED';
    public const PLACEMENT_WAITING = 'WAITING';

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
        'placement_status',
        'waiting_since',
        'placement_attempts',
        'last_placement_error',
        'last_placement_check_at',
    ];

    protected function casts(): array
    {
        return [
            'target_value'          => 'decimal:8',
            'share_percent'         => 'decimal:2',
            'amount_to_sell'        => 'decimal:8',
            'sell_ref_exchange_fee' => 'decimal:8',
            'exchange_order_id'     => 'string',
            'previous_exchange_order_id' => 'string',
            'filled_at'             => 'datetime',
            'migrated_at'           => 'datetime',
            'waiting_since'         => 'datetime',
            'placement_attempts'    => 'integer',
            'last_placement_check_at' => 'datetime',
        ];
    }

    public function botBuyExecution(): BelongsTo
    {
        return $this->belongsTo(BotBuyExecution::class);
    }
}
