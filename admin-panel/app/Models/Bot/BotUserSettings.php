<?php

namespace App\Models\Bot;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotUserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'auto_trade_enabled',
        'reinvest_enabled',      // stored only — no Phase 1 logic (D1)
        'terms_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'auto_trade_enabled' => 'boolean',
            'reinvest_enabled'   => 'boolean',
            'terms_accepted_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null;
    }
}
