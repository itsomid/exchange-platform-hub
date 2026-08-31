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
use App\Services\Bot\BotBuyAttemptRecorder;
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
 *      (min_deposit_usdt minus the transfer fee on that amount). REINVEST and
 *      ADMIN_BUY triggers are instead gated on the cheapest in-range signal's
 *      effective minimum, so already-present cash re-enters the cycle as soon
 *      as it can buy anything at all.
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
    public const TRIGGER_ADMIN_BUY   = 'ADMIN_BUY';

    public function __construct(
        private readonly SignalFilterService $filter,
        private readonly AllocationService $allocator,
        private readonly PriceFeed $priceFeed,
        private readonly BotOrderStatusService $orderStatus,
        private readonly FeeCalculator $feeCalculator,
        private readonly BotBuyAttemptRecorder $recorder,
    ) {}

    /**
     * @param int|null $sellOrderId the sell tier whose fill freed the balance,
     *                              recorded on the attempt so a stranded
     *                              REINVEST can be traced back to its source.
     */
    public function __invoke(
        int $userId,
        string $triggeredBy = self::TRIGGER_MANUAL,
        ?int $sellOrderId = null,
    ): ?BotOrder {
        $prep = $this->prepare($userId, $triggeredBy);

        if ($prep['blocked'] !== null) {
            $this->logNoOrder($userId, $triggeredBy, $prep['blocked'], $prep['context']);
            $this->recorder->record(
                $userId,
                $triggeredBy,
                $this->describe($prep, null, BotWallet::where('user_id', $userId)->first(), $triggeredBy),
                sellOrderId: $sellOrderId,
            );

            return null;
        }

        if ($prep['unpriced']->isNotEmpty()) {
            Log::warning('bot.orchestrator.signal_unpriced', [
                'user_id'               => $userId,
                'triggered_by'          => $triggeredBy,
                'unpriced_currency_ids' => $prep['unpriced']->pluck('currency_id')->all(),
            ]);
        }

        $wallet   = null;
        $sized    = null;
        $describe = null;
        $order    = null;

        // Reading the free balance, sizing the allocation against it and
        // writing the resulting lock must all observe the same wallet
        // snapshot. They used to straddle an unlocked read, so two sell
        // settlements completing at once on separate queue workers each
        // allocated the other's freed principal and drove locked_balance above
        // balance (negative withdrawable). The row lock serialises the whole
        // decision per user: a concurrent trigger blocks here and then re-reads
        // the post-commit balance.
        try {
            DB::transaction(function () use ($userId, $triggeredBy, $prep, &$wallet, &$sized, &$describe, &$order) {
                $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->first();
                if (! $wallet) {
                    $this->logNoOrder($userId, $triggeredBy, 'no_bot_wallet');
                    $describe = $this->describe($prep, ['blocked' => 'no_bot_wallet'], null, $triggeredBy);
                    return;
                }

                $sized = $this->sizeAllocation($wallet, $prep, $triggeredBy);

                // Snapshot the verdict before persistAndDispatch() raises
                // locked_balance, so the attempt records the numbers the
                // decision was actually made on.
                $describe = $this->describe($prep, $sized, $wallet, $triggeredBy);

                if ($sized['blocked'] !== null) {
                    $this->logNoOrder($userId, $triggeredBy, $sized['blocked'], $sized['context']);
                    return;
                }

                $order = $this->persistAndDispatch(
                    $userId,
                    $wallet,
                    $sized['free'],
                    $prep['alpha'],
                    $triggeredBy,
                    $sized['result'],
                    $prep['unpriced'],
                );
            });
        } catch (\Throwable $e) {
            // Recorded outside the transaction so the explanation survives the
            // rollback that just discarded the order.
            $describe ??= $this->describe($prep, $sized, $wallet, $triggeredBy);
            $this->recorder->record(
                $userId,
                $triggeredBy,
                $describe,
                sellOrderId: $sellOrderId,
                exception: $e,
            );

            throw $e;
        }

        $describe ??= $this->describe($prep, $sized, $wallet, $triggeredBy);
        $this->recorder->record($userId, $triggeredBy, $describe, $order, $sellOrderId);

        return $order;
    }

    /**
     * Dry-run of a buy cycle: what __invoke() would do for this user right now —
     * the wallet snapshot it would size against, the gate that snapshot must
     * clear, the resulting per-coin allocation and each coin's planned sell
     * tiers — without creating an order or locking a single USDT.
     *
     * Backs the admin panel's "buy from the free balance" confirmation modal,
     * so `blocked` is the verdict the real run would reach and its reason is
     * what the admin is shown when no purchase is possible.
     *
     * @return array<string, mixed>
     */
    public function preview(int $userId, string $triggeredBy = self::TRIGGER_ADMIN_BUY): array
    {
        $prep   = $this->prepare($userId, $triggeredBy);
        $wallet = BotWallet::where('user_id', $userId)->first();

        $sized = ($prep['blocked'] === null && $wallet !== null)
            ? $this->sizeAllocation($wallet, $prep, $triggeredBy)
            : null;

        return $this->describe($prep, $sized, $wallet, $triggeredBy);
    }

    /**
     * Render a prepare()/sizeAllocation() pair as the full verdict payload: the
     * wallet snapshot, the gate, the per-coin allocation and sell plan, and the
     * coins that were skipped, priced out of range or not priced at all.
     *
     * Shared by the admin preview and by the attempt recorder, so what an admin
     * sees in the modal and what lands in bot_buy_attempts can never disagree.
     *
     * @param array<string, mixed>      $prep  output of prepare()
     * @param array<string, mixed>|null $sized output of sizeAllocation(), null when prepare() already blocked
     * @return array<string, mixed>
     */
    private function describe(array $prep, ?array $sized, ?BotWallet $wallet, string $triggeredBy): array
    {
        $balance = $wallet ? (string) $wallet->balance : '0';
        $locked  = $wallet ? (string) $wallet->locked_balance : '0';
        $free    = bcsub($balance, $locked, 8);
        $result  = $sized['result'] ?? null;

        $signals = $prep['eligible']->keyBy('id');
        $total   = $result ? $result->totalAllocated() : '0';

        $allocations = [];
        foreach ($result?->allocations ?? [] as $alloc) {
            $allocations[] = $this->previewAllocation(
                $alloc,
                $signals->get($alloc['signal_id']),
                $total,
            );
        }

        $skipped = [];
        foreach ($result?->skipped ?? [] as $skip) {
            $skipped[] = [
                'currency_id'         => $skip['currency_id'],
                'currency_symbol'     => $this->symbolOf($signals->get($skip['signal_id'])),
                'would_have_received' => $skip['would_have_received'],
                'cap_exhausted'       => (bool) ($skip['cap_exhausted'] ?? false),
                'reason'              => $skip['reason'],
            ];
        }

        return [
            'ok'           => $prep['blocked'] === null && ($sized['blocked'] ?? null) === null,
            'reason'       => $prep['blocked'] ?? $sized['blocked'] ?? null,
            'context'      => $prep['blocked'] !== null ? $prep['context'] : ($sized['context'] ?? []),
            'triggered_by' => $triggeredBy,
            'wallet'       => [
                'balance'        => $balance,
                'locked_balance' => $locked,
                'free_balance'   => $free,
            ],
            'gate'         => [
                'amount'    => $sized['gate'] ?? $this->buyGate($prep, $triggeredBy),
                'kind'      => $this->gatesOnBuyFloor($triggeredBy) ? 'buy_floor' : 'min_deposit',
                'min_net'   => $prep['min_net'],
                'buy_floor' => $this->minEffectiveBuyFloor($prep['eligible'], $prep['floor_mode']),
            ],
            'allocations'  => $allocations,
            'skipped'      => $skipped,
            'unpriced'     => $prep['unpriced']->map(fn ($s) => [
                'currency_id'     => (int) $s->currency_id,
                'currency_symbol' => $this->symbolOf($s),
            ])->all(),
            'out_of_range' => $prep['out_of_range']->map(fn ($s) => [
                'currency_id'     => (int) $s->currency_id,
                'currency_symbol' => $this->symbolOf($s),
                'current_price'   => number_format((float) $s->getAttribute('live_price'), 8, '.', ''),
                'floor_price'     => (string) $s->floor_price,
                'ceiling_price'   => (string) $s->ceiling_price,
            ])->all(),
            'totals'       => [
                'free'            => $free,
                'total_allocated' => $total,
                'unallocated'     => bcsub($free, $total, 8),
                'allocated_count' => count($allocations),
                'skipped_count'   => count($skipped),
            ],
        ];
    }

    /**
     * Everything that has to happen before the wallet row is locked: the
     * user/global gates and the live-price classification of active signals.
     *
     * Reports a block as a reason string rather than logging or returning early
     * itself, so the live run and the admin preview judge a trigger by exactly
     * the same rules and can never drift apart.
     *
     * @return array{
     *     blocked: ?string,
     *     context: array<string, mixed>,
     *     eligible: Collection<int, BotSignal>,
     *     unpriced: Collection<int, BotSignal>,
     *     out_of_range: Collection<int, BotSignal>,
     *     candidates: array<int, array<string, mixed>>,
     *     alpha: string,
     *     floor_mode: string,
     *     min_net: string
     * }
     */
    private function prepare(int $userId, string $triggeredBy): array
    {
        $global = BotGlobalSettings::current();

        $prep = [
            'blocked'      => null,
            'context'      => [],
            'eligible'     => collect(),
            'unpriced'     => collect(),
            'out_of_range' => collect(),
            'candidates'   => [],
            'alpha'        => (string) $global->alpha_weight,
            'floor_mode'   => (string) ($global->precheck_floor_mode ?? 'multi'),
            'min_net'      => $this->feeCalculator->minNetDeposit(),
        ];

        $settings = BotUserSettings::where('user_id', $userId)->first();
        if (! $settings || ! $settings->auto_trade_enabled) {
            $prep['blocked'] = 'auto_trade_disabled';
            return $prep;
        }

        if (! $global->is_enabled) {
            $prep['blocked'] = 'bot_globally_disabled';
            return $prep;
        }

        $wallet = BotWallet::where('user_id', $userId)->first();
        if (! $wallet) {
            $prep['blocked'] = 'no_bot_wallet';
            return $prep;
        }

        // Unlocked fast path: keeps signal classification off the hot path for
        // wallets that plainly cannot buy. The binding check is the one under
        // the wallet row lock in sizeAllocation() — this read may already be
        // stale. Buy-floor-gated triggers are checked there against the
        // cheapest in-range minimum instead of the full min-deposit, which
        // needs the classification, so they skip this shortcut entirely.
        if (! $this->gatesOnBuyFloor($triggeredBy)) {
            $free = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);
            if (bccomp($free, $prep['min_net'], 8) < 0) {
                $prep['blocked'] = 'insufficient_free_balance';
                $prep['context'] = [
                    'balance'     => (string) $wallet->balance,
                    'locked'      => (string) $wallet->locked_balance,
                    'free'        => $free,
                    'min_deposit' => (string) $global->min_deposit_usdt,
                    'min_net'     => $prep['min_net'],
                    'gate'        => $prep['min_net'],
                ];
                return $prep;
            }
        }

        // Classify active signals: those we can price & trade vs. those whose
        // live price is missing entirely (a data problem we must surface as a
        // failure, not as a silent "no opportunity") vs. those simply priced
        // outside their window.
        $classified            = $this->filter->classify();
        $prep['eligible']      = $classified['eligible'];
        $prep['unpriced']      = $classified['unpriced'];
        $prep['out_of_range']  = $classified['out_of_range'];

        // Nothing eligible and nothing broken → genuinely no opportunity
        // (prices simply outside their windows, or no active signals).
        if ($prep['eligible']->isEmpty() && $prep['unpriced']->isEmpty()) {
            $prep['blocked'] = 'no_eligible_signals';
            $prep['context'] = ['active_signals' => BotSignal::active()->count()];
            return $prep;
        }

        $prep['candidates'] = $prep['eligible']->map(function ($signal) {
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

        return $prep;
    }

    /**
     * Size the allocation against a single wallet snapshot. A pure decision —
     * it writes nothing — so the live run (holding the row lock) and the admin
     * preview (holding no lock) are guaranteed to reach the same verdict.
     *
     * @param array<string, mixed> $prep output of prepare()
     * @return array{blocked: ?string, context: array<string, mixed>, free: string, gate: string, result: ?AllocationResult}
     */
    private function sizeAllocation(BotWallet $wallet, array $prep, string $triggeredBy): array
    {
        $free = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);
        $gate = $this->buyGate($prep, $triggeredBy);

        if (bccomp($free, $gate, 8) < 0) {
            return [
                'blocked' => 'insufficient_free_balance',
                'context' => [
                    'balance' => (string) $wallet->balance,
                    'locked'  => (string) $wallet->locked_balance,
                    'free'    => $free,
                    'gate'    => $gate,
                ],
                'free'    => $free,
                'gate'    => $gate,
                'result'  => null,
            ];
        }

        // Enforce max_allocation_percent against the TOTAL wallet balance minus
        // what each currency already holds, so re-buying from the freed
        // remainder (e.g. after toggling the bot off/on) can never push a coin
        // past its cap of the whole wallet.
        $committed = $this->committedUsdtPerCurrency((int) $wallet->user_id);
        $result = empty($prep['candidates'])
            ? new AllocationResult([], [], $free, [])
            : $this->allocator->allocate(
                $prep['candidates'],
                $free,
                $prep['alpha'],
                $prep['floor_mode'],
                (string) $wallet->balance,
                $committed,
            );

        // No buyable allocation → don't create an order at all. Skipped-only
        // outcomes (every coin already at its max_allocation_percent cap, or
        // below its tradeable minimum) carry no purchase, so recording an
        // order full of SKIPPED rows would just be noise. Unpriced signals are
        // the one exception: we still create the order so the underlying data
        // failure surfaces to the user instead of a misleading "no opportunity".
        if (empty($result->allocations) && $prep['unpriced']->isEmpty()) {
            return [
                'blocked' => 'no_buyable_allocation',
                'context' => [
                    'candidates' => count($prep['candidates']),
                    'skipped'    => count($result->skipped),
                    'free'       => $free,
                ],
                'free'    => $free,
                'gate'    => $gate,
                'result'  => $result,
            ];
        }

        return [
            'blocked' => null,
            'context' => [],
            'free'    => $free,
            'gate'    => $gate,
            'result'  => $result,
        ];
    }

    /**
     * Minimum free balance this trigger must clear before an order is sized.
     *
     * @param array<string, mixed> $prep output of prepare()
     */
    private function buyGate(array $prep, string $triggeredBy): string
    {
        if (! $this->gatesOnBuyFloor($triggeredBy)) {
            return $prep['min_net'];
        }

        // Only require enough free USDT to clear the smallest effective minimum
        // among in-range signals (same formula as the allocator's D14
        // pre-check), not the full min-deposit. Below that floor no signal
        // could receive a viable allocation anyway. With no eligible signal to
        // measure against, fall back to the legacy gate.
        return $this->minEffectiveBuyFloor($prep['eligible'], $prep['floor_mode']) ?? $prep['min_net'];
    }

    /**
     * Triggers that deploy cash the wallet already holds — principal freed by a
     * filled sell tier, or an admin pushing an idle free balance back to work —
     * are gated on the cheapest in-range buy floor rather than the full
     * min-deposit, so money that CAN buy something does not sit idle until
     * min_deposit_usdt of free cash piles up again.
     */
    private function gatesOnBuyFloor(string $triggeredBy): bool
    {
        return in_array($triggeredBy, [self::TRIGGER_REINVEST, self::TRIGGER_ADMIN_BUY], true);
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
     * One row of the admin preview table: what this coin would receive, at what
     * live price, and the sell ladder that would be opened on the resulting
     * position.
     *
     * @param array<string, mixed> $alloc an AllocationResult allocation entry
     * @return array<string, mixed>
     */
    private function previewAllocation(array $alloc, ?BotSignal $signal, string $totalAllocated): array
    {
        $amount = (string) $alloc['amount'];
        $price  = (string) $alloc['snapshot']['C'];
        $coin   = bccomp($price, '0', 8) > 0 ? bcdiv($amount, $price, 8) : '0';

        return [
            'signal_id'              => (int) $alloc['signal_id'],
            'currency_id'            => (int) $alloc['currency_id'],
            'currency_symbol'        => $this->symbolOf($signal),
            'currency_name'          => (string) ($signal?->currency?->persian_name ?: ($signal?->currency?->name ?? '')),
            'priority'               => (int) $alloc['snapshot']['priority'],
            'amount_usdt'            => $amount,
            'share_percent'          => bccomp($totalAllocated, '0', 8) > 0
                ? bcdiv(bcmul($amount, '100', 8), $totalAllocated, 4)
                : '0',
            'estimated_amount_coin'  => $coin,
            'current_price'          => $price,
            'floor_price'            => (string) $alloc['snapshot']['D'],
            'ceiling_price'          => (string) $alloc['snapshot']['E'],
            'min_buy_amount_usdt'    => (string) $alloc['snapshot']['min_buy_amount_usdt'],
            'max_allocation_percent' => (string) $alloc['snapshot']['max_allocation_percent'],
            'sell_plan'              => $this->previewSellPlan($signal, $coin, $price),
        ];
    }

    /**
     * The sell ladder this allocation would open once it fills, priced off the
     * CURRENT price. The live ladder is priced off the actual fill price, and
     * any tier landing below effective_p2p_min_order_value gets merged by the
     * smart collapse at that point — flagged per tier so the admin sees which
     * ones are at risk of being merged.
     *
     * @return array<string, mixed>
     */
    private function previewSellPlan(?BotSignal $signal, string $amountCoin, string $buyPrice): array
    {
        $targets = is_array($signal?->sell_targets) ? $signal->sell_targets : [];
        $p2pMin  = (string) ($signal?->effective_p2p_min_order_value ?? '0');
        $mode    = (string) ($signal?->sell_mode ?? 'percent');

        $tiers = [];
        foreach ($targets as $target) {
            $share   = number_format((float) ($target['share'] ?? 0), 8, '.', '');
            $trigger = number_format((float) ($target['trigger'] ?? 0), 8, '.', '');
            $type    = (string) ($target['type'] ?? $mode);

            $tierCoin  = bcdiv(bcmul($amountCoin, $share, 8), '100', 8);
            $sellPrice = $type === 'price'
                ? $trigger
                : bcmul($buyPrice, bcadd('1', bcdiv($trigger, '100', 10), 10), 8);

            $tiers[] = [
                'type'          => $type,
                'trigger'       => $trigger,
                'share_percent' => $share,
                'amount_coin'   => $tierCoin,
                'sell_price'    => $sellPrice,
                'revenue_usdt'  => bcmul($tierCoin, $sellPrice, 8),
                'cost_usdt'     => bcmul($tierCoin, $buyPrice, 8),
                'below_p2p_min' => bccomp(bcmul($tierCoin, $buyPrice, 8), $p2pMin, 8) < 0,
            ];
        }

        usort($tiers, fn ($a, $b) => bccomp($a['trigger'], $b['trigger'], 8));

        return [
            'mode'                => $mode,
            'count'               => (int) ($signal?->sell_orders_count ?? 0),
            'p2p_min_order_value' => $p2pMin,
            'tiers'               => $tiers,
        ];
    }

    private function symbolOf(?BotSignal $signal): string
    {
        return strtoupper((string) ($signal?->currency?->symbol ?? ''));
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
