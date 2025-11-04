<?php

namespace App\Events;

use App\Http\Resources\V1\Spot\OrderBookResource; // Add this import
use App\Services\Spot\SpotService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Responsibilities (English):
 * - Publish the latest Order Book snapshot for a given market.
 * - Implement `ShouldBroadcast` to emit updates on `order-book.{marketId}`.
 * - Build a normalized payload in `broadcastWith()` using `OrderBookResource`
 *   and `SpotService::getLatestOrderBook(...)`.
 * - Intended to be dispatched by `BroadcastOrderBook` after de-duplication,
 *   so clients receive one consolidated update per uniqueness window.
 * Notes:
 * - Carries `marketId` only; it does not handle throttling or uniqueness.
 * - Keep payload construction here to separate concerns from the Job.
 */
class OrderBookUpdated implements ShouldBroadcastNow
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
