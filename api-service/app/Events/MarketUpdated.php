<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use DateTime;

class MarketUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels, Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 5;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly int $marketId, private readonly array $data)
    {
        $this->onQueue('api-market');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('market.' . $this->marketId),
        ];
    }

    public function broadcastWith(): array
    {
        return $this->data;
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
