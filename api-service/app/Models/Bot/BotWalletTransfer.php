<?php

namespace App\Models\Bot;

use App\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotWalletTransfer extends Model
{
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'user_id',
        'bot_wallet_id',
        'wallet_id',
        'direction',
        'gross_amount',
        'fee',
        'net_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function botWallet(): BelongsTo
    {
        return $this->belongsTo(BotWallet::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
