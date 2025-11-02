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
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'response_time_ms',
        'requested_at',
        'created_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_time_ms' => 'integer',
        'requested_at' => 'datetime',
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
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if request failed
     */
    public function isFailed(): bool
    {
        return $this->response_status >= 400;
    }

    /**
     * Log API request
     */
    public static function logRequest(array $data): self
    {
        return static::create(array_merge($data, [
            'created_at' => Carbon::now()
        ]));
    }

    /**
     * Create a new request log with named parameters
     */
    public static function createLog(
        int $systemId,
        string $endpoint,
        string $method,
        string $ipAddress,
        ?string $userAgent = null,
        ?array $requestHeaders = null,
        ?string $requestBody = null,
        int $responseStatus,
        ?string $responseBody = null,
        ?int $responseTimeMs = null
    ): self {
        return static::create([
            'system_id' => $systemId,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_headers' => $requestHeaders,
            'request_body' => $requestBody,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'response_time_ms' => $responseTimeMs,
            'requested_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
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

    /**
     * Scope for requests by API system
     */
    public function scopeByApiSystem($query, int $apiSystemId)
    {
        return $query->where('system_id', $apiSystemId);
    }

    /**
     * Scope for requests by endpoint
     */
    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope for requests by method
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope for slow requests (response time > threshold)
     */
    public function scopeSlowRequests($query, float $threshold = 1000.0)
    {
        return $query->where('response_time_ms', '>', $threshold);
    }
}
