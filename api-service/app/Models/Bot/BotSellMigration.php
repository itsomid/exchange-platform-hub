<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSellMigration extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_FAILED  = 'FAILED';

    protected $fillable = [
        'currency_id',
        'admin_id',
        'admin_label',
        'status',
        'tiers_count',
        'placed_count',
        'completed_count',
        'failed_count',
        'required_amount',
        'details',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'required_amount' => 'decimal:8',
            'details'         => 'array',
            'started_at'      => 'datetime',
            'finished_at'     => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
