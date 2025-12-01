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
        return hash('sha256', 'production-token-' . time());
    }

    /**
     * Check if token is active
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
     * Toggle token status
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
     * Regenerate token
     */
    public function regenerate(): bool
    {
        $this->token = self::generateToken();
        $this->last_used_at = null;
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
     * Get formatted scopes
     */
    public function getFormattedScopesAttribute(): string
    {
        if (empty($this->scopes)) {
            return 'هیچ دسترسی';
        }

        $scopeLabels = [
            'user_balance' => 'موجودی کاربران',
            'stock_purchase' => 'خرید سهام',
            'reports' => 'گزارش‌گیری',
        ];

        $formatted = [];
        foreach ($this->scopes as $scope) {
            $formatted[] = $scopeLabels[$scope] ?? $scope;
        }

        return implode('، ', $formatted);
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-secondary">غیرفعال</span>';
        }

        if ($this->isExpired()) {
            return '<span class="badge bg-danger">منقضی شده</span>';
        }

        return '<span class="badge bg-success">فعال</span>';
    }

    /**
     * Get expiry status
     */
    public function getExpiryStatusAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'بدون انقضا';
        }

        if ($this->isExpired()) {
            return 'منقضی شده در ' . $this->expires_at->format('Y/m/d H:i');
        }

        $diffInDays = $this->expires_at->diffInDays(Carbon::now());
        if ($diffInDays <= 7) {
            return 'منقضی می‌شود در ' . $diffInDays . ' روز';
        }

        return 'منقضی می‌شود در ' . $this->expires_at->format('Y/m/d');
    }

    /**
     * Get masked token for display
     */
    public function getMaskedTokenAttribute(): string
    {
        return substr($this->token, 0, 8) . '...' . substr($this->token, -8);
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
}
