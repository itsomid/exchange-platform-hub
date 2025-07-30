<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 5;

    /**
     * Determine the time at which the job should timeout.
     */
    public int $retryUntil;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly int $marketId, private readonly array $data)
    {
        // Set retry until to 10 seconds from now
        $this->retryUntil = now()->addSeconds(10)->timestamp;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('market.'.$this->marketId),
        ];
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }

    /**
     * Get the queue the event should be dispatched to.
     */
    public function viaQueue(): string
    {
        return 'api-market';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure for monitoring
        \Log::warning('MarketUpdated event failed for market: ' . $this->marketId, [
            'market_id' => $this->marketId,
            'data' => $this->data,
            'exception' => $exception->getMessage(),
            'timeout_reason' => 'Market update processing exceeded timeout limit'
        ]);
    }
}
