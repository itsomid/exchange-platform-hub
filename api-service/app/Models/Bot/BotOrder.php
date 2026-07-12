<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotOrder extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'batch_uuid',
        'total_amount_usdt',
        'alpha_snapshot',
        'status',
        'description',
        'admin_description',
        'triggered_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount_usdt' => 'decimal:8',
            'alpha_snapshot'    => 'decimal:2',
            'completed_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyExecutions(): HasMany
    {
        return $this->hasMany(BotBuyExecution::class);
    }
}
