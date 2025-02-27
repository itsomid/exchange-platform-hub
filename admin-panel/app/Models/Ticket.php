<?php

namespace App\Models;

use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use Filterable;

    public $filterNameSpace = 'App\Filters\TicketFilters';

    protected $fillable = [
        'ticket_number', 'user_id', 'subject', 'status',
        'priority', 'closed_at', 'reopened_at', 'resolved_at',
        'ticketable_id', 'ticketable_type'
    ];
    protected $casts = [
        'status' => TicketStatusEnum::class, // Cast the status to the enum
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

    public function ticketable()
    {
        return $this->morphTo(__FUNCTION__, 'ticketable_type', 'ticketable_id');
    }


    public static function generateTicketNumber(): string
    {
        return 'TCK-'.strtoupper(Str::random(10));
    }
}
