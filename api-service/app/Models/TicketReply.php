<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TicketReply extends Model
{
    protected $fillable = ['ticket_id', 'repliable_id', 'repliable_type', 'message', 'is_private', 'is_seen'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function repliable(): MorphTo
    {
        return $this->morphTo();
    }
}
