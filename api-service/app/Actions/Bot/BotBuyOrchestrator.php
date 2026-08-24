<?php

namespace App\Actions\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Services\Bot\AllocationResult;
use App\Services\Bot\AllocationService;
use App\Services\Bot\BotOrderDescriptionService;
use App\Services\Bot\BotOrderStatusService;
use App\Services\Bot\FeeCalculator;
use App\Services\Bot\PriceFeed;
use App\Services\Bot\SignalFilterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orchestrates a full "trigger to buy" cycle for a single user:
 *
 *   1. Validate auto-trade is enabled and free_balance ≥ minNetDeposit
 *      (min_deposit_usdt minus the transfer fee on that amount). REINVEST
 *      triggers are instead gated on the cheapest in-range signal's effective
 *      minimum, so freed principal re-enters the cycle as soon as it can buy
 *      anything at all.
 *   2. Gather eligible signals and compute the allocation pipeline.
 *   3. Persist a single bot_orders row + one bot_buy_executions row per signal
 *      (allocated → PENDING, D14-rejected → SKIPPED with failure_reason).
 *   4. Lock the total non-SKIPPED allocation on the bot wallet.
 *   5. Dispatch one BuyExecutionJob per non-SKIPPED execution.
 *
 * NOTE: Reinvest path is NOT triggered here, even if reinvest_enabled = true.
 */
class BotBuyOrchestrator
{
    public const TRIGGER_TRANSFER_IN = 'TRANSFER_IN';
    public const TRIGGER_TOGGLE_ON   = 'TOGGLE_ON';
    public const TRIGGER_MANUAL      = 'MANUAL';
    public const TRIGGER_REINVEST    = 'REINVEST';
    public const TRIGGER_SIGNAL_SCAN = 'SIGNAL_SCAN';

    public function __construct(
        private readonly SignalFilterService $filter,
        private readonly AllocationService $allocator,
        private readonly PriceFeed $priceFeed,
        private readonly BotOrderStatusService $orderStatus,
        private readonly FeeCalculator $feeCalculator,
    ) {}

    public function __invoke(int $userId, string $triggeredBy = self::TRIGGER_MANUAL): ?BotOrder
    {
        $settings = BotUserSettings::where('user_id', $userId)->first();
        if (! $settings || ! $settings->auto_trade_enabled) {
            $this->logNoOrder($userId, $triggeredBy, 'auto_trade_disabled');
            return null;
        }

        $global = BotGlobalSettings::current();
        if (! $global->is_enabled) {
            $this->logNoOrder($userId, $triggeredBy, 'bot_globally_disabled');
            return null;
        }

        $wallet = BotWallet::where('user_id', $userId)->first();
        if (! $wallet) {
            $this->logNoOrder($userId, $triggeredBy, 'no_bot_wallet');
            return null;
        }

        $minNet = $this->feeCalculator->minNetDeposit();
        // Unlocked fast path: keeps signal classification off the hot path for
        // wallets that plainly cannot buy. The binding check is the one under
        // the wallet row lock further down — this read may already be stale.
        // REINVEST is gated there against the cheapest in-range buy floor
        // instead of the full min-deposit, so principal freed by a filled sell
        // tier goes straight back to work instead of idling until
        // min_deposit_usdt of free cash piles up.
        if ($triggeredBy !== self::TRIGGER_REINVEST) {
            $free = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);
            if (bccomp($free, $minNet, 8) < 0) {
                $this->logNoOrder($userId, $triggeredBy, 'insufficient_free_balance', [
                    'balance'      => (string) $wallet->balance,
                    'locked'       => (string) $wallet->locked_balance,
                    'free'         => $free,
                    'min_deposit'  => (string) $global->min_deposit_usdt,
                    'min_net'      => $minNet,
                ]);
                return null;
            }
        }

        // Classify active signals: those we can price & trade vs. those whose
        // live price is missing entirely (a data problem we must surface as a
        // failure, not as a silent "no opportunity").
        $classified = $this->filter->classify();
        $eligible   = $classified['eligible'];
        $unpriced   = $classified['unpriced'];

        // Nothing eligible and nothing broken → genuinely no opportunity
        // (prices simply outside their windows, or no active signals).
        if ($eligible->isEmpty() && $unpriced->isEmpty()) {
            $this->logNoOrder($userId, $triggeredBy, 'no_eligible_signals', [
                'active_signals' => BotSignal::active()->count(),
            ]);
            return null;
        }

        if ($unpriced->isNotEmpty()) {
            Log::warning('bot.orchestrator.signal_unpriced', [
                'user_id'               => $userId,
                'triggered_by'          => $triggeredBy,
                'unpriced_currency_ids' => $unpriced->pluck('currency_id')->all(),
            ]);
        }

        $candidates = $eligible->map(function ($signal) {
            return [
                'signal_id'                     => (int) $signal->id,
                'currency_id'                   => (int) $signal->currency_id,
                'priority'                      => (int) $signal->priority,
                'floor_price'                   => (string) $signal->floor_price,
                'ceiling_price'                 => (string) $signal->ceiling_price,
                // number_format: (string) on tiny floats yields "1.23E-8" which BCMath rejects.
                'current_price'                 => number_format((float) $signal->getAttribute('live_price'), 8, '.', ''),
                'min_buy_amount_usdt'           => (string) $signal->min_buy_amount_usdt,
                'max_allocation_percent'        => (string) $signal->max_allocation_percent,
                'sell_orders_count'             => (int) $signal->sell_orders_count,
                'effective_p2p_min_order_value' => (string) $signal->effective_p2p_min_order_value,
            ];
        })->all();

        $alpha  = (string) $global->alpha_weight;
        $floorMode = (string) ($global->precheck_floor_mode ?? 'multi');

        // Reading the free balance, sizing the allocation against it and
        // writing the resulting lock must all observe the same wallet
        // snapshot. They used to straddle an unlocked read, so two sell
        // settlements completing at once on separate queue workers each
        // allocated the other's freed principal and drove locked_balance above
        // balance (negative withdrawable). The row lock serialises the whole
        // decision per user: a concurrent trigger blocks here and then re-reads
        // the post-commit balance.
        return DB::transaction(function () use ($userId, $triggeredBy, $candidates, $eligible, $unpriced, $alpha, $floorMode, $minNet) {
            $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (! $wallet) {
                $this->logNoOrder($userId, $triggeredBy, 'no_bot_wallet');
                return null;
            }

            $free = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);

            // Reinvest gate: only require enough free USDT to clear the smallest
            // effective minimum among in-range signals (same formula as the
            // allocator's D14 pre-check), not the full min-deposit. Below that
            // floor no signal could receive a viable allocation anyway. With no
            // eligible signal to measure against, fall back to the legacy gate.
            $gate = $triggeredBy === self::TRIGGER_REINVEST
                ? ($this->minEffectiveBuyFloor($eligible, $floorMode) ?? $minNet)
                : $minNet;

            if (bccomp($free, $gate, 8) < 0) {
                $this->logNoOrder($userId, $triggeredBy, 'insufficient_free_balance', [
                    'balance' => (string) $wallet->balance,
                    'locked'  => (string) $wallet->locked_balance,
                    'free'    => $free,
                    'gate'    => $gate,
                ]);
                return null;
            }

            // Enforce max_allocation_percent against the TOTAL wallet balance minus
            // what each currency already holds, so re-buying from the freed
            // remainder (e.g. after toggling the bot off/on) can never push a coin
            // past its cap of the whole wallet.
            $committed = $this->committedUsdtPerCurrency($userId);
            $result = empty($candidates)
                ? new AllocationResult([], [], $free, [])
                : $this->allocator->allocate($candidates, $free, $alpha, $floorMode, (string) $wallet->balance, $committed);

            // No buyable allocation → don't create an order at all. Skipped-only
            // outcomes (every coin already at its max_allocation_percent cap, or
            // below its tradeable minimum) carry no purchase, so recording an
            // order full of SKIPPED rows would just be noise. Unpriced signals are
            // the one exception: we still create the order so the underlying data
            // failure surfaces to the user instead of a misleading "no opportunity".
            if (empty($result->allocations) && $unpriced->isEmpty()) {
                $this->logNoOrder($userId, $triggeredBy, 'no_buyable_allocation', [
                    'candidates' => count($candidates),
                    'skipped'    => count($result->skipped),
                    'free'       => $free,
                ]);
                return null;
            }

            return $this->persistAndDispatch($userId, $wallet, $free, $alpha, $triggeredBy, $result, $unpriced);
        });
    }

    /**
     * Record exactly why a trigger produced no bot order. Previously every one
     * of these early exits returned null silently, so a "bot turned on but
     * nothing happened" outcome left no trace in the logs at all.
     */
    private function logNoOrder(int $userId, string $triggeredBy, string $reason, array $context = []): void
    {
        Log::info('bot.orchestrator.no_order', array_merge([
            'user_id'      => $userId,
            'triggered_by' => $triggeredBy,
            'reason'       => $reason,
        ], $context));
    }

    /**
     * Smallest USDT amount that could still produce a buy across the given
     * in-range signals: min over signals of max(min_buy_amount_usdt, order
     * floor), where the order floor mirrors the allocator's D14 pre-check
     * (effective_p2p_min_order_value, multiplied by sell_orders_count in
     * 'multi' floor mode). Returns null when there are no eligible signals.
     *
     * @param Collection<int, BotSignal> $eligible
     */
    private function minEffectiveBuyFloor(Collection $eligible, string $floorMode): ?string
    {
        $min = null;

        foreach ($eligible as $signal) {
            $p2pMin     = (string) $signal->effective_p2p_min_order_value;
            $orderFloor = $floorMode === 'single'
                ? $p2pMin
                : bcmul((string) (int) $signal->sell_orders_count, $p2pMin, 8);
            $minBuy       = (string) $signal->min_buy_amount_usdt;
            $effectiveMin = bccomp($minBuy, $orderFloor, 8) >= 0 ? $minBuy : $orderFloor;

            if ($min === null || bccomp($effectiveMin, $min, 8) < 0) {
                $min = $effectiveMin;
            }
        }

        return $min;
    }

    /**
     * USDT still committed to each currency across the user's live bot
     * positions. Used as the per-currency offset when enforcing
     * max_allocation_percent against the total wallet balance.
     *
     * Two sources, so partially-exited positions release headroom as their
     * sell tiers fill (rather than staying "fully committed" until the whole
     * position exits):
     *
     *   1. In-flight buys (PENDING/BUYING) and freshly-bought positions with no
     *      sell tiers opened yet lock their FULL allocated_usdt.
     *   2. Bought positions still holding coin count only the cost basis of the
     *      tiers whose sell order is still OPEN (amount_to_sell × avg_buy_price).
     *      Tiers already sold have returned their principal to the free balance,
     *      so they no longer count against the currency's cap.
     *
     * @return array<int, string> currency_id => committed_usdt
     */
    private function committedUsdtPerCurrency(int $userId): array
    {
        $committed = [];

        // (1) Full allocation for in-flight buys and bought-but-not-yet-selling.
        $fullAlloc = BotBuyExecution::query()
            ->whereHas('botOrder', fn ($q) => $q->where('user_id', $userId))
            ->where(function ($q) {
                $q->whereIn('status', [
                    BotBuyExecution::STATUS_PENDING,
                    BotBuyExecution::STATUS_BUYING,
                ])->orWhere(function ($q2) {
                    $q2->where('status', BotBuyExecution::STATUS_BOUGHT)
                        ->whereDoesntHave('sellOrders');
                });
            })
            ->groupBy('currency_id')
            ->selectRaw('currency_id, SUM(allocated_usdt) as committed')
            ->pluck('committed', 'currency_id');

        foreach ($fullAlloc as $currencyId => $amount) {
            $committed[(int) $currencyId] = bcadd(
                $committed[(int) $currencyId] ?? '0',
                (string) $amount,
                8,
            );
        }

        // (2) Remaining held cost basis of still-OPEN sell tiers.
        $openSellCostBasis = BotSellOrder::query()
            ->where('bot_sell_orders.status', BotSellOrder::STATUS_OPEN)
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $userId)
            ->groupBy('bot_buy_executions.currency_id')
            ->selectRaw('bot_buy_executions.currency_id as currency_id, SUM(bot_sell_orders.amount_to_sell * bot_buy_executions.avg_buy_price) as committed')
            ->pluck('committed', 'currency_id');

        foreach ($openSellCostBasis as $currencyId => $amount) {
            $committed[(int) $currencyId] = bcadd(
                $committed[(int) $currencyId] ?? '0',
                (string) $amount,
                8,
            );
        }

        return array_map(fn ($v) => (string) $v, $committed);
    }

    /**
     * @param BotWallet $wallet Already locked with lockForUpdate() by the caller,
     *                          inside the same transaction.
     */
    private function persistAndDispatch(
        int $userId,
        BotWallet $wallet,
        string $totalAmount,
        string $alpha,
        string $triggeredBy,
        AllocationResult $result,
        Collection $unpriced,
    ): BotOrder {
        $botOrder = BotOrder::create([
            'user_id'           => $userId,
            'batch_uuid'        => (string) Str::uuid(),
            'total_amount_usdt' => $totalAmount,
            'alpha_snapshot'    => $alpha,
            'status'            => 'PENDING',
            'triggered_by'      => $triggeredBy,
        ]);

        $totalLock     = '0';
        $pendingExecs  = [];

        foreach ($result->allocations as $alloc) {
            $exec = BotBuyExecution::create([
                'bot_order_id'               => $botOrder->id,
                'currency_id'                => $alloc['currency_id'],
                'signal_snapshot'            => $alloc['snapshot'],
                'original_sell_orders_count' => $alloc['snapshot']['sell_orders_count'],
                'effective_sell_orders_count' => null,
                'allocated_usdt'             => $alloc['amount'],
                'status'                     => BotBuyExecution::STATUS_PENDING,
            ]);
            $totalLock      = bcadd($totalLock, $alloc['amount'], 8);
            $pendingExecs[] = $exec->id;
        }

        foreach ($result->skipped as $skip) {
            // A coin already at its max_allocation_percent cap has no remaining
            // headroom; it would be skipped in every future order too, so we
            // don't clutter the new order with it. Genuine below-min skips are
            // still recorded (they're informative and can change next cycle).
            if (! empty($skip['cap_exhausted'])) {
                continue;
            }

            BotBuyExecution::create([
                'bot_order_id'               => $botOrder->id,
                'currency_id'                => $skip['currency_id'],
                'signal_snapshot'            => $skip['snapshot'],
                'original_sell_orders_count' => $skip['snapshot']['sell_orders_count'],
                'effective_sell_orders_count' => null,
                'allocated_usdt'             => $skip['would_have_received'],
                'status'                     => BotBuyExecution::STATUS_SKIPPED,
                'failure_reason'             => $skip['reason'],
            ]);
        }

        // Signals we couldn't price at all become FAILED executions (no funds
        // locked, no exchange call). They surface in the order's failed count
        // so the activation overlay shows a real failure / partial outcome
        // instead of a misleading "no opportunity" state.
        foreach ($unpriced as $signal) {
            $exec = BotBuyExecution::create([
                'bot_order_id'               => $botOrder->id,
                'currency_id'                => $signal->currency_id,
                'signal_snapshot'            => [
                    'signal_id'         => (int) $signal->id,
                    'currency_id'       => (int) $signal->currency_id,
                    'priority'          => (int) $signal->priority,
                    'floor_price'       => (string) $signal->floor_price,
                    'ceiling_price'     => (string) $signal->ceiling_price,
                    'sell_orders_count' => (int) $signal->sell_orders_count,
                ],
                'original_sell_orders_count'  => $signal->sell_orders_count,
                'effective_sell_orders_count' => null,
                'allocated_usdt'              => '0',
                'status'                      => BotBuyExecution::STATUS_FAILED,
                'failure_reason'              => 'bot.buy.no_live_price',
            ]);

            app(BotOrderDescriptionService::class)->appendSystemNote($exec, 'bot.buy.no_live_price');
        }

        if (bccomp($totalLock, '0', 8) > 0) {
            $newLocked = bcadd((string) $wallet->locked_balance, $totalLock, 8);

            // The allocator is capped at the free balance read under this same
            // lock, so this can only trip on a genuine logic bug. Abort loudly
            // rather than reserve money the wallet does not hold — the whole
            // order (and its executions) rolls back with the transaction.
            if (bccomp($newLocked, (string) $wallet->balance, 8) > 0) {
                throw new RuntimeException(sprintf(
                    'bot.orchestrator.overlock user=%d balance=%s locked=%s requested=%s',
                    $userId,
                    (string) $wallet->balance,
                    (string) $wallet->locked_balance,
                    $totalLock,
                ));
            }

            $wallet->update(['locked_balance' => $newLocked]);
        }

        // No buy jobs will run (e.g. every eligible signal was unpriced and
        // recorded straight as FAILED). Nothing async will ever revisit this
        // order, so settle its own status right now instead of leaving it
        // stuck at PENDING.
        if (empty($pendingExecs)) {
            $this->orderStatus->finalizeIfAllFailed($botOrder->id);
        }

        // Dispatch jobs after commit so workers don't read uncommitted rows.
        DB::afterCommit(function () use ($pendingExecs) {
            foreach ($pendingExecs as $execId) {
                \App\Jobs\Bot\BuyExecutionJob::dispatch($execId)->onQueue('bot-buy');
            }
        });

        $userEmail = optional($botOrder->user)->email;

        Log::channel('smart-bot')->info("bot.orchestrator.dispatched.{$userEmail}", [
            'user_id'        => $userId,
            'user_email'     => $userEmail,
            'bot_order_id'   => $botOrder->id,
            'allocated'      => count($result->allocations),
            'skipped'        => count($result->skipped),
            'unpriced'       => $unpriced->count(),
            'total_locked'   => $totalLock,
            'unallocated'    => $result->unallocatedRemainder,
        ]);

        return $botOrder;
    }
}
