<?php

namespace App\Services\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Finalizes a bot order's own status based on the terminal state of its
 * buy executions. The order is created PENDING and, until now, was never
 * moved off PENDING except by an explicit cancel — so an order whose every
 * signal failed to buy would stay "PENDING" (shown as "در حال رصد") forever.
 *
 * Scope is intentionally narrow: we only promote a still-PENDING order to
 * FAILED when it has settled (no PENDING/BUYING executions left) and NOT a
 * single execution ended up BOUGHT. Orders with at least one bought signal
 * keep their existing behavior.
 *
 * When the whole order fails this way AND it was the cycle started by the
 * user turning the bot on (TOGGLE_ON), auto-trade is switched off so they
 * notice the first buy did not go through. Later cycles (SIGNAL_SCAN,
 * REINVEST, TRANSFER_IN, …) stay on so a transient exchange error cannot
 * park free capital until the user notices.
 */
class BotOrderStatusService
{
    public function __construct(private readonly BotAutoTradeToggleService $toggle) {}

    public function finalizeIfAllFailed(int $botOrderId): void
    {
        DB::transaction(function () use ($botOrderId) {
            $order = BotOrder::where('id', $botOrderId)->lockForUpdate()->first();
            if (! $order || $order->status !== 'PENDING') {
                return;
            }

            $counts = BotBuyExecution::where('bot_order_id', $botOrderId)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');

            $inFlight = (int) ($counts[BotBuyExecution::STATUS_PENDING] ?? 0)
                      + (int) ($counts[BotBuyExecution::STATUS_BUYING] ?? 0);
            $bought   = (int) ($counts[BotBuyExecution::STATUS_BOUGHT] ?? 0);
            $failed   = (int) ($counts[BotBuyExecution::STATUS_FAILED] ?? 0);

            // Still working, or at least one signal was bought → leave as-is.
            if ($inFlight > 0 || $bought > 0 || $failed === 0) {
                return;
            }

            $order->update([
                'status'       => 'FAILED',
                'completed_at' => now(),
            ]);

            $disableBot = $order->triggered_by === BotBuyOrchestrator::TRIGGER_TOGGLE_ON;
            if ($disableBot) {
                $this->toggle->disableBecauseAllBuysFailed($order->user_id, $order->id);
            }

            Log::warning('bot.order.failed_all', [
                'bot_order_id' => $order->id,
                'user_id'      => $order->user_id,
                'triggered_by' => $order->triggered_by,
                'auto_trade'   => $disableBot ? 'disabled' : 'unchanged',
            ]);
        });
    }
}
