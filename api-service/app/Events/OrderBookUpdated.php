<?php

namespace App\Events;

use App\Http\Resources\V1\Spot\OrderBookResource; // Add this import
use App\Services\Spot\SpotService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderBookUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $marketId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $marketId)
    {
        $this->marketId = $marketId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('order-book.' . $this->marketId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $spotOrderService = resolve(SpotService::class);
        return (new OrderBookResource($spotOrderService->getLatestOrderBook(
            marketId: $this->marketId,
            limit: config('spot.order_book_limit_count')
        )))->resolve(); // Use resolve() to get the array representation
    }
}
