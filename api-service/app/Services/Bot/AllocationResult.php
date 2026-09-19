<?php

namespace App\Services\Bot;

/**
 * Immutable result of running the allocation pipeline.
 *
 * @phpstan-type Allocation array{
 *     signal_id: int,
 *     currency_id: int,
 *     amount: string,
 *     weight: string,
 *     snapshot: array<string, mixed>
 * }
 * @phpstan-type Skipped array{
 *     signal_id: int,
 *     currency_id: int,
 *     reason: string,
 *     would_have_received: string,
 *     snapshot: array<string, mixed>
 * }
 */
class AllocationResult
{
    /**
     * @param array<int, Allocation> $allocations
     * @param array<int, Skipped>    $skipped
     * @param string                 $unallocatedRemainder
     * @param array<string, mixed>   $snapshot
     */
    public function __construct(
        public readonly array $allocations,
        public readonly array $skipped,
        public readonly string $unallocatedRemainder,
        public readonly array $snapshot,
    ) {}

    public function totalAllocated(): string
    {
        $sum = '0';
        foreach ($this->allocations as $a) {
            $sum = bcadd($sum, $a['amount'], 8);
        }

        return $sum;
    }
}
