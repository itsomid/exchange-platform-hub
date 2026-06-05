<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositDetected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly array $depositData,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.notifications.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DepositDetected';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'deposit_detected',
            'deposit' => $this->depositData,
        ];
    }
}
