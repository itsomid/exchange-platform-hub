<?php

namespace App\Repositories\Interfaces;

use App\Enums\ApiRequestType;
use App\Models\ApiSystem\ApiRequest;

interface ApiRequestRepositoryInterface
{
    /**
     * Create a new API request
     */
    public function create(array|object $data): ApiRequest;

    /**
     * Find an API request by ID
     */
    public function findById(int $id): ?ApiRequest;

    /**
     * Find API requests by type
     */
    public function findByType(ApiRequestType $type): \Illuminate\Database\Eloquent\Collection;

    /**
     * Find API requests by user ID
     */
    public function findByUserId(int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Update an API request
     */
    public function update(int $id, array $data): bool;

    /**
     * Mark request as completed
     */
    public function markAsCompleted(int $id, array $responseData = []): bool;

    /**
     * Mark request as failed
     */
    public function markAsFailed(int $id, string $failureReason): bool;

    /**
     * Get API requests by tracking code
     */
    public function getByTrackingCode(string $trackingCode): \Illuminate\Database\Eloquent\Collection;

    /**
     * Check if tracking code exists
     */
    public function existsByTrackingCode(string $trackingCode): bool;
}
