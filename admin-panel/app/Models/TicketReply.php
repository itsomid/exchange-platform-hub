<?php

namespace App\Models;

use App\Enums\TicketRepliableTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id', 'repliable_id', 'repliable_type',
        'message', 'is_private', 'is_seen', 'image'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function repliable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getImageUrl(): string
    {
        return $this->image ? asset($this->image) : asset('images/defaults/no-image.png');
    }

    public function getRepliableName(): string
    {
        return $this->repliable ? $this->repliable->fullName() : 'Unknown';
    }

    public function getRepliableAvatar(): string
    {
        if ($this->repliable) {
            if ($this->repliable instanceof \App\Models\User) {
                return $this->repliable->profile_picture
                    ? asset($this->repliable->profile_picture)
                    : asset('images/avatars/avatar.webp');
            } elseif ($this->repliable instanceof \App\Models\Admin) {
                return $this->repliable->avatar();
            }
        }

        return asset('images/avatars/avatar.webp');
    }

    public function getRepliableRole(): string
    {
        if ($this->repliable instanceof \App\Models\Admin) {
            return $this->repliable->roles()->first()->persian_name ?? 'مدیر'; // Return the role field from the Admin model, default to "مدیر"
        }

        return TicketRepliableTypeEnum::fromModelClass($this->repliable_type)?->label() ?? 'Unknown';
    }
}
