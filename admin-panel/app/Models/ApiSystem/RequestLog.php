<?php

namespace App\Models\ApiSystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RequestLog extends Model
{
    use HasFactory;

    protected $connection = 'api_system_db';
    protected $table = 'requests_log';

    protected $fillable = [
        'system_id',
        'token_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'response_code',
        'response_time',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'response_time' => 'float',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the API system that made this request
     */
    public function apiSystem(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }



    /**
     * Get the token used for this request
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(SystemToken::class, 'token_id');
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response_code >= 200 && $this->response_code < 300;
    }

    /**
     * Check if request failed
     */
    public function isFailed(): bool
    {
        return $this->response_code >= 400;
    }

    /**
     * Get response status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->isSuccessful()) {
            return '<span class="badge bg-success">' . $this->response_code . '</span>';
        } elseif ($this->response_code >= 400 && $this->response_code < 500) {
            return '<span class="badge bg-warning">' . $this->response_code . '</span>';
        } elseif ($this->response_code >= 500) {
            return '<span class="badge bg-danger">' . $this->response_code . '</span>';
        } else {
            return '<span class="badge bg-info">' . $this->response_code . '</span>';
        }
    }

    /**
     * Get method badge
     */
    public function getMethodBadgeAttribute(): string
    {
        $badges = [
            'GET' => '<span class="badge bg-primary">GET</span>',
            'POST' => '<span class="badge bg-success">POST</span>',
            'PUT' => '<span class="badge bg-warning">PUT</span>',
            'PATCH' => '<span class="badge bg-info">PATCH</span>',
            'DELETE' => '<span class="badge bg-danger">DELETE</span>',
        ];

        return $badges[$this->method] ?? '<span class="badge bg-secondary">' . $this->method . '</span>';
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time < 1) {
            return round($this->response_time * 1000) . ' ms';
        }

        return round($this->response_time, 2) . ' s';
    }

    /**
     * Get truncated endpoint
     */
    public function getTruncatedEndpointAttribute(): string
    {
        if (strlen($this->endpoint) > 50) {
            return substr($this->endpoint, 0, 47) . '...';
        }

        return $this->endpoint;
    }

    /**
     * Get formatted request data
     */
    public function getFormattedRequestDataAttribute(): string
    {
        if (empty($this->request_data)) {
            return 'بدون داده';
        }

        $json = json_encode($this->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return strlen($json) > 200 ? substr($json, 0, 197) . '...' : $json;
    }

    /**
     * Get formatted response data
     */
    public function getFormattedResponseDataAttribute(): string
    {
        if (empty($this->response_data)) {
            return 'بدون داده';
        }

        $json = json_encode($this->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return strlen($json) > 200 ? substr($json, 0, 197) . '...' : $json;
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_code', [200, 299]);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * Scope for today's requests
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for this week's requests
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's requests
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year);
    }
}
