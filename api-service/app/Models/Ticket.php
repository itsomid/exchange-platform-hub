<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'subject', 'status', 'priority', 'closed_at', 'reopened_at', 'resolved_at', 'ticketable_id', 'ticketable_type', 'image',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public static function generateTicketNumber(): string
    {
        return 'TCK-'.strtoupper(Str::random(10));
    }
}
