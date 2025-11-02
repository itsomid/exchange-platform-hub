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
        'allowed_ips',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
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
        return $this->hasMany(SystemToken::class)->where('is_active', true);
    }



    /**
     * Get all request logs for this API system
     */
    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    /**
     * Get all API requests for this system
     */
    public function apiRequests(): HasMany
    {
        return $this->hasMany(ApiRequest::class);
    }

    /**
     * Get pending API requests for this system
     */
    public function pendingApiRequests(): HasMany
    {
        return $this->hasMany(ApiRequest::class)->where('status', 'pending');
    }

    /**
     * Check if the system is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Toggle system status
     */
    public function toggleStatus(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
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
     * Get formatted permissions
     */
    public function getFormattedPermissionsAttribute(): string
    {
        return 'مدیریت از طریق توکن‌ها';
    }

    /**
     * Get formatted allowed IPs
     */
    public function getFormattedAllowedIpsAttribute(): string
    {
        if (empty($this->allowed_ips)) {
            return 'همه IP ها';
        }

        return implode('، ', $this->allowed_ips);
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="badge bg-success">فعال</span>'
            : '<span class="badge bg-danger">غیرفعال</span>';
    }

    /**
     * Get tokens count
     */
    public function getTokensCountAttribute(): int
    {
        return $this->tokens()->count();
    }

    /**
     * Get active tokens count
     */
    public function getActiveTokensCountAttribute(): int
    {
        return $this->activeTokens()->count();
    }
}
