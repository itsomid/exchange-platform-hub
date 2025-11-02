<?php

namespace App\Models\ApiSystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class System extends Model
{
    use HasFactory;

    protected $connection = 'api_system_db';
    protected $table = 'systems';

    protected $fillable = [
        'name',
        'description',
        'contact_email',
        'allowed_ips',
        'permissions',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get all tokens for this API system
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(SystemToken::class);
    }

    /**
     * Get active tokens for this API system
     */
    public function activeTokens(): HasMany
    {
        return $this->hasMany(SystemToken::class)->active()->notExpired();
    }



    /**
     * Get API request logs for this system
     */
    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    /**
     * Check if the system is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
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
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No restrictions
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if system has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Scope a query to only include active systems
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by permission
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->whereJsonContains('permissions', $permission);
    }

    /**
     * Find system by token
     */
    public static function findByToken(string $token): ?self
    {
        $tokenModel = SystemToken::where('token', $token)
            ->active()
            ->notExpired()
            ->first();

        return $tokenModel ? $tokenModel->apiSystem : null;
    }
}
