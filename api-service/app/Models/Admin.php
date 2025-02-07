<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

class Admin extends Model
{
    use Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->morphToMany(
            Role::class,
            'model',
            'model_has_roles',
            'model_id',
            'role_id'
        );
    }

    public function scopeRole(Builder $query, $roles): Builder
    {
        return $query->whereHas('roles', fn (Builder $subQuery) => $subQuery
            ->whereIn('name', Arr::wrap($roles))
        );
    }
}
