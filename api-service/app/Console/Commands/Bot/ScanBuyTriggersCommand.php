<?php

namespace App\Console\Commands\Bot;

use App\Jobs\Bot\SignalScanBuyJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Services\Bot\PriceFeed;
use Illuminate\Console\Command;

/**
 * Detects new buy opportunities coming from active bot signals and, when one
 * appears, runs a buy cycle for every eligible user (edge-triggered):
 *
 *   1. Activation  — an admin created/re-activated a signal (admin-panel sets
 *      bot_signals.activation_pending_at). Consumed and cleared here.
 *   2. Price entered — a signal was active but its live price sat outside
 *      [floor, ceiling] and has now moved inside. Detected by comparing the
 *      freshly computed in-range state against the stored price_in_range
 *      snapshot (false -> true transition).
 *
 * On a null (never-evaluated) snapshot we only seed the value and do NOT treat
 * it as a transition, so deploying this feature can't trigger a mass buy for
 * signals that were already in range.
 *
 * Scheduled every minute via routes/console.php (withoutOverlapping).
 */
class ScanBuyTriggersCommand extends Command
{
    protected $signature = 'bot:scan-buy-triggers';

    protected $description = 'Detect new signal buy opportunities (activation or price entering range) and run a buy round for eligible users';

    public function handle(PriceFeed $priceFeed): int
    {
        $signals = BotSignal::active()->get();

        $activationSignalIds = [];
        $activationPending   = false;
        $priceTransition     = false;

        foreach ($signals as $signal) {
            if ($signal->activation_pending_at !== null) {
                $activationPending     = true;
                $activationSignalIds[] = $signal->id;
            }

            try {
                $price = $priceFeed->getLive((int) $signal->currency_id);
            } catch (\Throwable) {
                // Unpriced: can't evaluate range this run; leave snapshot as-is.
                continue;
            }

            $inRange = $price >= (float) $signal->floor_price
                    && $price <= (float) $signal->ceiling_price;

            // Only an explicit out-of-range -> in-range move is an opportunity.
            // A null snapshot (first observation) is seeded without triggering.
            if ($inRange && $signal->price_in_range === false) {
                $priceTransition = true;
            }

            if ($signal->price_in_range !== $inRange) {
                $signal->update(['price_in_range' => $inRange]);
            }
        }

        $dispatched = 0;
        if ($activationPending || $priceTransition) {
            $dispatched = $this->dispatchEligibleUsers();
        }

        // Clear the activation markers we handled this run, regardless of how
        // many users were dispatched (the activation event is now consumed).
        if (! empty($activationSignalIds)) {
            BotSignal::whereIn('id', $activationSignalIds)->update(['activation_pending_at' => null]);
        }

        $this->info(sprintf(
            'bot:scan-buy-triggers active=%d activation=%d price_transition=%d dispatched=%d',
            $signals->count(),
            $activationPending ? 1 : 0,
            $priceTransition ? 1 : 0,
            $dispatched,
        ));

        return self::SUCCESS;
    }

    /**
     * Dispatch a buy job per eligible user: auto-trade enabled, free balance
     * (balance - locked_balance) >= min_deposit_usdt, and no in-flight buy
     * cycle (to avoid overlapping batches). The orchestrator re-gates all of
     * this, so this pre-filter is purely to avoid dispatching no-op jobs.
     */
    private function dispatchEligibleUsers(): int
    {
        $minDeposit = (string) BotGlobalSettings::current()->min_deposit_usdt;

        $autoTradeUserIds = BotUserSettings::query()
            ->where('auto_trade_enabled', true)
            ->pluck('user_id');

        if ($autoTradeUserIds->isEmpty()) {
            return 0;
        }

        $eligibleUserIds = BotWallet::query()
            ->whereIn('user_id', $autoTradeUserIds)
            ->whereRaw('balance - locked_balance >= ?', [$minDeposit])
            ->pluck('user_id');

        if ($eligibleUserIds->isEmpty()) {
            return 0;
        }

        $inFlightUserIds = BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->whereIn('bot_buy_executions.status', [
                BotBuyExecution::STATUS_PENDING,
                BotBuyExecution::STATUS_BUYING,
            ])
            ->whereIn('bot_orders.user_id', $eligibleUserIds)
            ->distinct()
            ->pluck('bot_orders.user_id');

        $targetUserIds = $eligibleUserIds->diff($inFlightUserIds)->values();

        foreach ($targetUserIds as $userId) {
            SignalScanBuyJob::dispatch((int) $userId)->onQueue('bot-buy');
        }

        return $targetUserIds->count();
    }
}
