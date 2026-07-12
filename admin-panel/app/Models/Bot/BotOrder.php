<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotOrder extends Model
{
    use HasFactory;

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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['PENDING', 'PARTIALLY_FILLED']);
    }
}
