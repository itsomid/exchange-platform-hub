<?php

namespace App\Models\ApiSystem;

use App\Enums\ApiRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ApiRequest extends Model
{
    use HasFactory;

    protected $connection = 'api_system_db';
    protected $table = 'api_requests';

    protected $fillable = [
        'system_id',
        'user_id',
        'type',
        'model_type',
        'model_id',
        'status',
        'failure_reason',
        'tracking_code',
        'user_data',
        'processed_at',
        'request_data',
        'response_data',
    ];

    protected $casts = [
        'type' => ApiRequestType::class,
        'user_data' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the API system that owns this request
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * Get the related model (polymorphic relationship)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by request type
     */
    public function scopeOfType($query, ApiRequestType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for user balance requests
     */
    public function scopeUserBalance($query)
    {
        return $query->where('type', ApiRequestType::USER_BALANCE);
    }

    /**
     * Scope for stock purchase requests
     */
    public function scopeStockPurchase($query)
    {
        return $query->where('type', ApiRequestType::STOCK_PURCHASE);
    }

    /**
     * Mark request as processing
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark request as completed
     */
    public function markAsCompleted(array $responseData = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'response_data' => $responseData,
        ]);
    }

    /**
     * Mark request as failed
     */
    public function markAsFailed(string $reason, array $responseData = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
            'response_data' => $responseData,
        ]);
    }

    /**
     * Get the display name for the request type
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return $this->type->getDisplayName();
    }

    /**
     * Get the description for the request type
     */
    public function getTypeDescriptionAttribute(): string
    {
        return $this->type->getDescription();
    }

    /**
     * Check if request is for user balance
     */
    public function isUserBalanceRequest(): bool
    {
        return $this->type === ApiRequestType::USER_BALANCE;
    }

    /**
     * Check if request is for stock purchase
     */
    public function isStockPurchaseRequest(): bool
    {
        return $this->type === ApiRequestType::STOCK_PURCHASE;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->amount) {
            return formatNumberTrimZeros($this->amount, 8);
        }
        return '0';
    }

    /**
     * Get formatted quantity
     */
    public function getFormattedQuantityAttribute(): string
    {
        if ($this->quantity) {
            return formatNumberTrimZeros($this->quantity, 8);
        }
        return '0';
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'processing' => 'badge-info',
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار',
            'processing' => 'در حال پردازش',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            default => 'نامشخص',
        };
    }
}
