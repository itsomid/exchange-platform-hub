<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use Illuminate\Support\Facades\DB;

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
 */
class BotOrderStatusService
{
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
        });
    }
}
