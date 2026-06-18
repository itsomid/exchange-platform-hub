<?php

namespace App\Listeners\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotSellOrder;
use App\Services\Bot\SettlementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleBotSellOrderFilled implements ShouldQueue
{
    public string $queue = 'bot-settlement';

    public function __construct(private readonly SettlementService $settlement) {}

    public function handle(BotSellOrderFilled $event): void
    {
        if ($event->sellOrder->status !== BotSellOrder::STATUS_OPEN) {
            return;
        }

        try {
            $this->settlement->settleFill(
                $event->sellOrder,
                $event->filledAmount,
                $event->fillPrice,
                $event->networkFee,
                $event->exchangeFee,
                $event->spreadFee,
            );
        } catch (\Throwable $e) {
            Log::error('bot.settlement.failed', [
                'sell_order_id' => $event->sellOrder->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
