<?php

namespace App\Listeners\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Events\Bot\BotWalletDeposited;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleBotWalletDeposited implements ShouldQueue
{
    public string $queue = 'bot-buy';

    public function __construct(private readonly BotBuyOrchestrator $orchestrator) {}

    public function handle(BotWalletDeposited $event): void
    {
        ($this->orchestrator)($event->userId, BotBuyOrchestrator::TRIGGER_TRANSFER_IN);
    }
}
