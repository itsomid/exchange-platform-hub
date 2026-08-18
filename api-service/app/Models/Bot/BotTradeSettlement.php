<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotTradeSettlement extends Model
{
    protected $fillable = [
        'user_id',
        'bot_buy_execution_id',
        'bot_sell_order_id',
        'gross_revenue',
        'cost_basis',
        'network_fee',
        'exchange_fee',
        'performance_fee',
        'referral_fee',
        'referral_user_id',
        'cancel_fee',
        'net_pnl',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_revenue'   => 'decimal:8',
            'cost_basis'      => 'decimal:8',
            'network_fee'     => 'decimal:8',
            'exchange_fee'    => 'decimal:8',
            'performance_fee' => 'decimal:8',
            'referral_fee'    => 'decimal:8',
            'cancel_fee'      => 'decimal:8',
            'net_pnl'         => 'decimal:8',
            'settled_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function botBuyExecution(): BelongsTo
    {
        return $this->belongsTo(BotBuyExecution::class);
    }

    public function botSellOrder(): BelongsTo
    {
        return $this->belongsTo(BotSellOrder::class);
    }

    public function referralUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referral_user_id');
    }
}
