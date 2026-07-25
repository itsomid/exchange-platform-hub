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
                $event->spreadFee,
            );

            // Reinvest the freed principal + realized profit back into a new buy
            // cycle. The orchestrator self-gates on auto_trade_enabled and only
            // opens a new order once free_balance reaches minNetDeposit, so
            // when auto-trade is off this is a no-op and freed funds simply stay
            // available for withdrawal.
            ($this->orchestrator)($settlement->user_id, BotBuyOrchestrator::TRIGGER_REINVEST);
        } catch (\Throwable $e) {
            Log::error('bot.settlement.failed', [
                'sell_order_id' => $event->sellOrder->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
