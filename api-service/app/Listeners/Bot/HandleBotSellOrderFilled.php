<?php

namespace App\Listeners\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotSellOrder;
use App\Services\Bot\SettlementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleBotSellOrderFilled implements ShouldQueue
{
    public string $queue = 'bot-settlement';

    public function __construct(
        private readonly SettlementService $settlement,
        private readonly BotBuyOrchestrator $orchestrator,
    ) {}

    public function handle(BotSellOrderFilled $event): void
    {
        if ($event->sellOrder->status !== BotSellOrder::STATUS_OPEN) {
            return;
        }

        try {
            $settlement = $this->settlement->settleFill(
                $event->sellOrder,
                $event->filledAmount,
                $event->fillPrice,
                $event->networkFee,
                $event->exchangeFee,
            );
        } catch (\Throwable $e) {
            Log::error('bot.settlement.failed', [
                'sell_order_id' => $event->sellOrder->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }

        // Reinvest the freed principal + realized profit back into a new buy
        // cycle. The orchestrator self-gates on auto_trade_enabled and on the
        // cheapest in-range buy floor, so when auto-trade is off this is a no-op
        // and freed funds simply stay available for withdrawal.
        //
        // Kept OUT of the settlement try/catch on purpose: rethrowing here used
        // to retry the whole listener, but the retry returns immediately (the
        // sell order is no longer OPEN), so the reinvest was silently lost. The
        // orchestrator has already recorded the failed attempt in
        // bot_buy_attempts by the time this catch runs.
        try {
            ($this->orchestrator)(
                $settlement->user_id,
                BotBuyOrchestrator::TRIGGER_REINVEST,
                (int) $event->sellOrder->id,
            );
        } catch (\Throwable $e) {
            Log::error('bot.reinvest.failed', [
                'sell_order_id' => $event->sellOrder->id,
                'user_id'       => $settlement->user_id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
