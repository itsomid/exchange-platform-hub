<?php

namespace App\Models;

use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'subject', 'status',
        'priority', 'closed_at', 'reopened_at', 'resolved_at',
        'ticketable_id', 'ticketable_type'
    ];
    protected $casts = [
        'status' => TicketStatusEnum::class, // Cast the status to the enum
        'ticketable_type' => TicketTypeEnum::class, // Cast the status to the enum
        'priority' => TicketPriorityEnum::class, // Cast the status to the enum
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function ticketable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generateTicketNumber(): string
    {
        return 'TCK-'.strtoupper(Str::random(10));
    }
}
