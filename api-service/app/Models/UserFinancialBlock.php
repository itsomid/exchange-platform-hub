<?php

namespace App\Models;

use App\Enums\UserFinancialBlockAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method Builder activeRestriction()
 * @property Carbon $restricted_until
 */
class UserFinancialBlock extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'restricted_until',
    ];

    protected function casts(): array
    {
        return [
            'restricted_until' => 'datetime',
            'action' => UserFinancialBlockAction::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->restricted_until->isPast();
    }

    public function scopeActiveRestriction(Builder $query): Builder
    {
        return $query->where('restricted_until', '>', Carbon::now());
    }
}
