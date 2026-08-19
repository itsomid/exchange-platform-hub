<?php

namespace App\Services\Bot;

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
 * When (and only when) the whole order fails this way, the user's auto-trade
 * is also switched off so the bot doesn't keep retrying a setup that just
 * failed end-to-end. A partial failure (some coins bought) never reaches this
 * branch, so in that case the bot stays on.
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

            // Whole order failed → turn auto-trade off (no BotAutoTradeToggled
            // event: the toggle listener only acts on OFF→ON). Partial failures
            // never get here, so a bot with at least one successful buy stays on.
            $this->toggle->disableBecauseAllBuysFailed($order->user_id, $order->id);

            Log::warning('bot.order.failed_all', [
                'bot_order_id' => $order->id,
                'user_id'      => $order->user_id,
                'auto_trade'   => 'disabled',
            ]);
        });
    }
}
