<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCodeUsage extends Model
{
    protected $fillable = [
        'referral_code_id', 'used_by', 'transaction_id', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
