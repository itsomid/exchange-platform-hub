<?php

namespace App\Repositories;

use App\Enums\ApiRequestType;
use App\Models\ApiSystem\ApiRequest;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ApiRequestRepository implements ApiRequestRepositoryInterface
{
    /**
     * Create a new API request
     */
    public function create(array|object $data): ApiRequest
    {
        // If data is a DTO, convert it to array
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }
        
        return ApiRequest::create($data);
    }

    /**
     * Find an API request by ID
     */
    public function findById(int $id): ?ApiRequest
    {
        return ApiRequest::find($id);
    }

    /**
     * Find API requests by type
     */
    public function findByType(ApiRequestType $type): Collection
    {
        return ApiRequest::where('type', $type)->get();
    }

    /**
     * Find API requests by user ID
     */
    public function findByUserId(int $userId): Collection
    {
        return ApiRequest::where('user_id', $userId)->get();
    }

    /**
     * Update an API request
     */
    public function update(int $id, array $data): bool
    {
        return ApiRequest::where('id', $id)->update($data);
    }

    /**
     * Mark request as completed
     */
    public function markAsCompleted(int $id, array $responseData = []): bool
    {
        return $this->update($id, [
            'status' => 'completed',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark request as failed
     */
    public function markAsFailed(int $id, string $failureReason): bool
    {
        return $this->update($id, [
            'status' => 'failed',
            'failure_reason' => $failureReason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get API requests by tracking code
     */
    public function getByTrackingCode(string $trackingCode): Collection
    {
        return ApiRequest::where('tracking_code', $trackingCode)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if tracking code exists
     */
    public function existsByTrackingCode(string $trackingCode): bool
    {
        return ApiRequest::where('tracking_code', $trackingCode)->exists();
    }
}