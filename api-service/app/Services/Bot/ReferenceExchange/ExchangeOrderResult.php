<?php

namespace App\Services\Bot\ReferenceExchange;

/**
 * Immutable result of a single reference-exchange operation (place / get / etc.).
 * All numeric strings are scale-8 BCMath-friendly.
 */
final class ExchangeOrderResult
{
    public function __construct(
        public readonly ?string $exchangeOrderId,
        public readonly ExchangeOrderStatus $status,
        public readonly string $filledAmount = '0',
        public readonly string $avgPrice = '0',
        public readonly string $exchangeFee = '0',
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public function isFilled(): bool
    {
        return $this->status === ExchangeOrderStatus::FILLED;
    }

    public function isOpen(): bool
    {
        return $this->status === ExchangeOrderStatus::OPEN
            || $this->status === ExchangeOrderStatus::PARTIAL;
    }
}
