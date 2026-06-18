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

    protected $fillable = [
        'bot_buy_execution_id',
        'exchange_order_id',
        'target_type',
        'target_value',
        'share_percent',
        'amount_to_sell',
        'status',
        'filled_at',
    ];

    protected function casts(): array
    {
        return [
            'target_value'  => 'decimal:8',
            'share_percent' => 'decimal:2',
            'amount_to_sell' => 'decimal:8',
            'filled_at'     => 'datetime',
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

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'OPEN');
    }
}
