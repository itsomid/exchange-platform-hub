<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail of every attempt to deploy a user's free bot balance: which
 * trigger fired, the wallet snapshot it saw, the gate it had to clear and
 * either the order it produced or the exact reason it produced none.
 */
class BotBuyAttempt extends Model
{
    public const UPDATED_AT = null;

    public const OUTCOME_ORDER_CREATED = 'ORDER_CREATED';
    public const OUTCOME_BLOCKED       = 'BLOCKED';
    public const OUTCOME_EXCEPTION     = 'EXCEPTION';

    protected $fillable = [
        'user_id',
        'bot_order_id',
        'bot_sell_order_id',
        'triggered_by',
        'outcome',
        'reason_code',
        'reason_message',
        'balance',
        'locked_balance',
        'free_balance',
        'gate_amount',
        'gate_kind',
        'total_allocated',
        'allocated_count',
        'skipped_count',
        'out_of_range_count',
        'unpriced_count',
        'details',
        'exception',
    ];

    protected function casts(): array
    {
        return [
            'details'         => 'array',
            'balance'         => 'decimal:8',
            'locked_balance'  => 'decimal:8',
            'free_balance'    => 'decimal:8',
            'gate_amount'     => 'decimal:8',
            'total_allocated' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function botOrder(): BelongsTo
    {
        return $this->belongsTo(BotOrder::class);
    }
}
