<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'principal_balance',
        'profit_balance',
        'locked_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance'           => 'decimal:8',
            'principal_balance' => 'decimal:8',
            'profit_balance'    => 'decimal:8',
            'locked_balance'    => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFreeBalanceAttribute(): string
    {
        return bcsub((string) $this->balance, (string) $this->locked_balance, 8);
    }
}
