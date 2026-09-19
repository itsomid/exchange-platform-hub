<?php

namespace App\Console\Commands\Bot;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Jobs\Bot\SignalScanBuyJob;
use App\Models\Bot\BotBuyAttempt;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotWallet;
use App\Services\Bot\PriceFeed;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Detects buy opportunities for money already sitting in users' bot wallets and
 * runs a buy cycle for everyone who can act on them. Edge-triggered on two
 * independent sides, because either one alone leaves money stranded:
 *
 *   SIGNAL EDGE — a new opportunity appeared, so re-evaluate every eligible user:
 *     1. Activation   — an admin created/re-activated a signal (admin-panel sets
 *        bot_signals.activation_pending_at). Consumed and cleared here.
 *     2. Price entered — a signal was active but its live price sat outside
 *        [floor, ceiling] and has now moved inside. Detected by comparing the
 *        freshly computed in-range state against the stored price_in_range
 *        snapshot (false -> true transition).
 *
 *   BALANCE EDGE — a single user's deployable balance changed since the scan
 *     last evaluated them (a sell tier filled, an order was canceled, a deposit
 *     landed), so that user alone is re-evaluated even though no signal moved.
 *
 * On a null (never-evaluated) price_in_range snapshot we only seed the value and
 * do NOT treat it as a transition, so deploying this feature can't trigger a
 * mass buy for signals that were already in range.
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
        $inRange             = collect();

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

            $isInRange = $price >= (float) $signal->floor_price
                      && $price <= (float) $signal->ceiling_price;

            if ($isInRange) {
                $inRange->push($signal);
            }

            // Only an explicit out-of-range -> in-range move is an opportunity.
            // A null snapshot (first observation) is seeded without triggering.
            if ($isInRange && $signal->price_in_range === false) {
                $priceTransition = true;
            }

            if ($signal->price_in_range !== $isInRange) {
                $signal->update(['price_in_range' => $isInRange]);
            }
        }

        $dispatched = $this->dispatchEligibleUsers($inRange, $activationPending || $priceTransition);

        // Clear the activation markers we handled this run, regardless of how
        // many users were dispatched (the activation event is now consumed).
        if (! empty($activationSignalIds)) {
            BotSignal::whereIn('id', $activationSignalIds)->update(['activation_pending_at' => null]);
        }

        $this->info(sprintf(
            'bot:scan-buy-triggers active=%d in_range=%d activation=%d price_transition=%d dispatched=%d',
            $signals->count(),
            $inRange->count(),
            $activationPending ? 1 : 0,
            $priceTransition ? 1 : 0,
            $dispatched,
        ));

        return self::SUCCESS;
    }

    /**
     * Dispatch a buy job per eligible user: auto-trade enabled, free balance
     * (balance - locked_balance) at or above the cheapest in-range buy floor,
     * and no in-flight buy cycle (to avoid overlapping batches).
     *
     * The floor is the cheapest amount that could actually buy something right
     * now — NOT min_deposit_usdt, which is the minimum for transferring new
     * money in. Gating the scan on the deposit minimum is what let a freed
     * balance below it fall out of the automation permanently.
     *
     * The orchestrator re-gates all of this, so this pre-filter exists purely to
     * avoid dispatching no-op jobs.
     *
     * @param Collection<int, BotSignal> $inRange
     */
    private function dispatchEligibleUsers(Collection $inRange, bool $signalEdge): int
    {
        // Nothing is priced inside its window: no free balance, however large,
        // could buy anything this run.
        $buyFloor = $this->cheapestBuyFloor($inRange);
        if ($buyFloor === null) {
            return 0;
        }

        // Free balance is derived and compared with bcmath rather than in SQL:
        // the value is matched for exact equality against a stored decimal
        // further down, and drivers disagree on how a bound decimal compares
        // against a numeric column expression.
        $candidates = BotWallet::query()
            ->join('bot_user_settings', 'bot_user_settings.user_id', '=', 'bot_wallets.user_id')
            ->where('bot_user_settings.auto_trade_enabled', true)
            ->select('bot_wallets.user_id', 'bot_wallets.balance', 'bot_wallets.locked_balance')
            ->get()
            ->mapWithKeys(fn (BotWallet $w) => [
                (int) $w->user_id => bcsub((string) $w->balance, (string) $w->locked_balance, 8),
            ])
            ->filter(fn (string $free) => bccomp($free, $buyFloor, 8) >= 0);

        if ($candidates->isEmpty()) {
            return 0;
        }

        $userIds = $candidates->keys()->all();

        $inFlightUserIds = BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->whereIn('bot_buy_executions.status', [
                BotBuyExecution::STATUS_PENDING,
                BotBuyExecution::STATUS_BUYING,
            ])
            ->whereIn('bot_orders.user_id', $userIds)
            ->distinct()
            ->pluck('bot_orders.user_id')
            ->all();

        $lastEvaluated = $this->lastEvaluatedFreeBalances($userIds);

        $dispatched = 0;

        foreach ($candidates as $userId => $free) {
            if (in_array($userId, $inFlightUserIds, true)) {
                continue;
            }

            $seen        = $lastEvaluated[$userId] ?? null;
            $balanceEdge = $seen === null || bccomp($free, $seen, 8) !== 0;

            // Neither a new opportunity nor new money: the scan already reached
            // its verdict on exactly this balance and would only reproduce it.
            if (! $signalEdge && ! $balanceEdge) {
                continue;
            }

            SignalScanBuyJob::dispatch($userId)->onQueue('bot-buy');
            $dispatched++;
        }

        return $dispatched;
    }

    /**
     * Cheapest amount that could still produce a buy across the in-range
     * signals, or null when none of them is in range.
     *
     * @param Collection<int, BotSignal> $inRange
     */
    private function cheapestBuyFloor(Collection $inRange): ?string
    {
        $floorMode = (string) (BotGlobalSettings::current()->precheck_floor_mode ?? 'multi');
        $min       = null;

        foreach ($inRange as $signal) {
            $effectiveMin = $signal->effectiveMinBuyUsdt($floorMode);

            if ($min === null || bccomp($effectiveMin, $min, 8) < 0) {
                $min = $effectiveMin;
            }
        }

        return $min;
    }

    /**
     * The free balance this scan last evaluated for each user, read from the
     * attempts it recorded. Lets a user be re-run the moment their deployable
     * balance moves, without re-running everyone every minute.
     *
     * @param array<int, int> $userIds
     * @return array<int, string> user_id => free_balance
     */
    private function lastEvaluatedFreeBalances(array $userIds): array
    {
        $latestIds = BotBuyAttempt::query()
            ->whereIn('user_id', $userIds)
            ->where('triggered_by', BotBuyOrchestrator::TRIGGER_SIGNAL_SCAN)
            ->groupBy('user_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return [];
        }

        return BotBuyAttempt::whereIn('id', $latestIds)
            ->get(['user_id', 'free_balance'])
            ->mapWithKeys(fn (BotBuyAttempt $a) => [(int) $a->user_id => (string) $a->free_balance])
            ->all();
    }
}
