<?php

namespace App\Repositories\DTO\ApiSystem;

use App\Enums\ApiRequestType;

class CreateApiRequestDTO
{
    private int $systemId;
    private int $userId;
    private ApiRequestType $type;
    private ?string $referenceId = null;
    private ?string $trackingCode = null;
    private string $status = 'pending';
    private ?string $modelType = null;
    private ?int $modelId = null;
    private array $userData = [];
    private array $requestData = [];
    private array $responseData = [];

    public function getSystemId(): int
    {
        return $this->systemId;
    }

    public function setSystemId(int $systemId): self
    {
        $this->systemId = $systemId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getType(): ApiRequestType
    {
        return $this->type;
    }

    public function setType(ApiRequestType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getModelType(): ?string
    {
        return $this->modelType;
    }

    public function setModelType(?string $modelType): self
    {
        $this->modelType = $modelType;
        return $this;
    }

    public function getModelId(): ?int
    {
        return $this->modelId;
    }

    public function setModelId(?int $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function setUserData(array $userData): self
    {
        $this->userData = $userData;
        return $this;
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function setRequestData(array $requestData): self
    {
        $this->requestData = $requestData;
        return $this;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function setResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }

    /**
     * Convert DTO to array for repository
     */
    public function toArray(): array
    {
        return [
            'system_id' => $this->systemId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'reference_id' => $this->referenceId,
            'tracking_code' => $this->trackingCode,
            'status' => $this->status,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'user_data' => $this->userData,
            'request_data' => $this->requestData,
            'response_data' => $this->responseData,
        ];
    }
}