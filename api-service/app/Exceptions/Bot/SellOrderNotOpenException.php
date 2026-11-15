<?php

namespace App\Exceptions\Bot;

use RuntimeException;

/**
 * Thrown when a sell tier is about to be settled but is no longer OPEN
 * (another fill / cancel already settled it).
 */
class SellOrderNotOpenException extends RuntimeException
{
    public function __construct(public readonly int $sellOrderId, public readonly string $status)
    {
        parent::__construct("Sell order #{$sellOrderId} is no longer OPEN ({$status}).");
    }
}
