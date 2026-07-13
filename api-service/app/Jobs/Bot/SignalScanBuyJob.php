<?php

namespace App\Jobs\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs a buy cycle for a single user in response to a new signal opportunity
 * detected by bot:scan-buy-triggers (an admin (re)activation, or a signal's
 * live price entering its [floor, ceiling] window). The orchestrator re-gates
 * everything (auto-trade on, free balance, eligibility, per-currency caps), so
 * dispatching this job is safe even if the user is no longer eligible.
 */
class SignalScanBuyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $userId)
    {
        $this->onQueue('bot-buy');
    }

    public function handle(BotBuyOrchestrator $orchestrator): void
    {
        ($orchestrator)($this->userId, BotBuyOrchestrator::TRIGGER_SIGNAL_SCAN);
    }
}
