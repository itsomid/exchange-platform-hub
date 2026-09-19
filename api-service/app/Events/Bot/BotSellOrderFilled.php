<?php

namespace App\Events\Bot;

use App\Models\Bot\BotSellOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitted when a bot sell order's target price is reached and the sell should
 * be settled. Whatever component is responsible for triggering the fill
 * (price watcher, cron, manual admin action, …) dispatches this event with
 * the fill details. HandleBotSellOrderFilled then runs SettlementService on
 * the bot-settlement queue.
 */
class BotSellOrderFilled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BotSellOrder $sellOrder,
        public readonly string $filledAmount,
        public readonly string $fillPrice,
        public readonly string $networkFee = '0',
        public readonly string $exchangeFee = '0',
    ) {}
}
