<?php

namespace App\Models\Bot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSellOrder extends Model
{
    public const STATUS_OPEN     = 'OPEN';
    public const STATUS_FILLED   = 'FILLED';
    public const STATUS_CANCELED = 'CANCELED';

    protected $fillable = [
        'bot_buy_execution_id',
        'exchange_order_id',
        'target_type',
        'target_value',
        'share_percent',
        'amount_to_sell',
        'sell_ref_exchange_fee',
        'status',
        'filled_at',
    ];

    protected function casts(): array
    {
        return [
            'target_value'          => 'decimal:8',
            'share_percent'         => 'decimal:2',
            'amount_to_sell'        => 'decimal:8',
            'sell_ref_exchange_fee' => 'decimal:8',
            'exchange_order_id'     => 'string',
            'filled_at'             => 'datetime',
        ];
    }

    public function botBuyExecution(): BelongsTo
    {
        return $this->belongsTo(BotBuyExecution::class);
    }
}
