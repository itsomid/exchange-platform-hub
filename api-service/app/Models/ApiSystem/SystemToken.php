<?php

namespace App\Models\ApiSystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SystemToken extends Model
{
    use HasFactory;

    protected $connection = 'api_system_db';
    protected $table = 'system_tokens';

    protected $fillable = [
        'system_id',
        'token',
        'name',
        'scopes',
        'expires_at',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the API system that owns this token
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * Generate a new token
     */
    public static function generateToken(): string
    {
        return 'btr_' . Str::random(40);
    }

    /**
     * Check if token is active and not expired
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): bool
    {
        $this->last_used_at = Carbon::now();
        return $this->save();
    }

    /**
     * Check if token has scope
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        return in_array($scope, $this->scopes);
    }

    /**
     * Scope a query to only include active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-expired tokens
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Scope a query to only include expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope a query to filter by scope
     */
    public function scopeWithScope($query, string $scope)
    {
        return $query->whereJsonContains('scopes', $scope);
    }

    /**
     * Find active token by token string
     */
    public static function findActiveToken(string $token): ?self
    {
        return static::where('token', $token)
            ->active()
            ->notExpired()
            ->first();
    }

    /**
     * Validate token and return with system
     */
    public static function validateToken(string $token): ?array
    {
        $tokenModel = static::findActiveToken($token);

        if (!$tokenModel || !$tokenModel->apiSystem->isActive()) {
            return null;
        }

        return [
            'token' => $tokenModel,
            'system' => $tokenModel->apiSystem
        ];
    }
}
