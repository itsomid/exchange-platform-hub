<?php

namespace App\DTO\UserAccount;

use App\Enums\ProductAccessType;
use Carbon\Carbon;

class ProductAccessResponseDTO
{
    private int $userId;

    private int $productId;

    private ?Carbon $effectiveFromDateTime = null;

    private ?Carbon $effectiveToDateTime = null;

    private ProductAccessType $accessReasonType;

    /**
     * @return $this
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return $this
     */
    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getEffectiveFromDateTime(): ?Carbon
    {
        return $this->effectiveFromDateTime;
    }

    /**
     * @return $this
     */
    public function setEffectiveFromDateTime(?Carbon $effectiveFromDateTime): self
    {
        $this->effectiveFromDateTime = $effectiveFromDateTime;

        return $this;
    }

    public function getEffectiveToDateTime(): ?Carbon
    {
        return $this->effectiveToDateTime;
    }

    /**
     * @return $this
     */
    public function setEffectiveToDateTime(?Carbon $effectiveToDateTime): self
    {
        $this->effectiveToDateTime = $effectiveToDateTime;

        return $this;
    }

    public function getAccessReasonType(): ProductAccessType
    {
        return $this->accessReasonType;
    }

    /**
     * @return $this
     */
    public function setAccessReasonType(ProductAccessType $accessReasonType): self
    {
        $this->accessReasonType = $accessReasonType;

        return $this;
    }
}
