<?php

namespace App\Listeners\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Events\Bot\BotAutoTradeToggled;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Triggers the buy orchestrator only on an OFF→ON transition.
 * The event carries the new state; the toggle endpoint only fires the event
 * when the value actually changes (so receiving `enabled=true` here implies
 * off→on by construction).
 */
class HandleBotAutoTradeToggled implements ShouldQueue
{
    public string $queue = 'bot-buy';

    public function __construct(private readonly BotBuyOrchestrator $orchestrator) {}

    public function handle(BotAutoTradeToggled $event): void
    {
        if (! $event->enabled) {
            return;
        }

        ($this->orchestrator)($event->userId, BotBuyOrchestrator::TRIGGER_TOGGLE_ON);
    }
}
