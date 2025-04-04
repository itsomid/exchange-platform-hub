<?php

namespace App\Events;

use App\Models\SpotTrade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class MarketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly int $marketId, private readonly array $data)
    {
        //
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
        return array_merge($this->data, ['volume' => $this->getVolume()]);
    }

    private function getVolume()
    {
        return Cache::remember('MarketUpdated.getVolume', 60, fn()=> SpotTrade::query()
            ->where('market_id', $this->marketId)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity'));
    }
}
