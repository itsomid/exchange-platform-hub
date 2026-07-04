<?php

namespace App\Actions\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Services\Bot\AllocationResult;
use App\Services\Bot\AllocationService;
use App\Services\Bot\PriceFeed;
use App\Services\Bot\SignalFilterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates a full "trigger to buy" cycle for a single user:
 *
 *   1. Validate auto-trade is enabled and free_balance ≥ min_deposit_usdt.
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

    public function __construct(
        private readonly SignalFilterService $filter,
        private readonly AllocationService $allocator,
        private readonly PriceFeed $priceFeed,
    ) {}

    public function __invoke(int $userId, string $triggeredBy = self::TRIGGER_MANUAL): ?BotOrder
    {
        $settings = BotUserSettings::where('user_id', $userId)->first();
        if (! $settings || ! $settings->auto_trade_enabled) {
            return null;
        }

        $global = BotGlobalSettings::current();
        if (! $global->is_enabled) {
            return null;
        }

        $wallet = BotWallet::where('user_id', $userId)->first();
        if (! $wallet) {
            return null;
        }

        $free       = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);
        $minDeposit = (string) $global->min_deposit_usdt;
        if (bccomp($free, $minDeposit, 8) < 0) {
            return null;
        }

        // Build candidate set from eligible signals.
        $signals = $this->filter->eligibleSignals();
        if ($signals->isEmpty()) {
            return null;
        }

        $candidates = $signals->map(function ($signal) {
            return [
                'signal_id'                     => (int) $signal->id,
                'currency_id'                   => (int) $signal->currency_id,
                'priority'                      => (int) $signal->priority,
                'floor_price'                   => (string) $signal->floor_price,
                'ceiling_price'                 => (string) $signal->ceiling_price,
                'current_price'                 => (string) $signal->getAttribute('live_price'),
                'min_buy_amount_usdt'           => (string) $signal->min_buy_amount_usdt,
                'max_allocation_percent'        => (string) $signal->max_allocation_percent,
                'sell_orders_count'             => (int) $signal->sell_orders_count,
                'effective_p2p_min_order_value' => (string) $signal->effective_p2p_min_order_value,
            ];
        })->all();

        $alpha  = (string) $global->alpha_weight;
        $floorMode = (string) ($global->precheck_floor_mode ?? 'multi');
        $result = $this->allocator->allocate($candidates, $free, $alpha, $floorMode);

        if (empty($result->allocations) && empty($result->skipped)) {
            return null;
        }

        return DB::transaction(function () use ($userId, $free, $alpha, $triggeredBy, $result) {
            return $this->persistAndDispatch($userId, $free, $alpha, $triggeredBy, $result);
        });
    }

    private function persistAndDispatch(
        int $userId,
        string $totalAmount,
        string $alpha,
        string $triggeredBy,
        AllocationResult $result,
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

        if (bccomp($totalLock, '0', 8) > 0) {
            // Lock funds on the bot wallet (row lock for atomicity).
            $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->first();
            $wallet->update([
                'locked_balance' => bcadd((string) $wallet->locked_balance, $totalLock, 8),
            ]);
        }

        // Dispatch jobs after commit so workers don't read uncommitted rows.
        DB::afterCommit(function () use ($pendingExecs) {
            foreach ($pendingExecs as $execId) {
                \App\Jobs\Bot\BuyExecutionJob::dispatch($execId)->onQueue('bot-buy');
            }
        });

        Log::info('bot.orchestrator.dispatched', [
            'user_id'        => $userId,
            'bot_order_id'   => $botOrder->id,
            'allocated'      => count($result->allocations),
            'skipped'        => count($result->skipped),
            'total_locked'   => $totalLock,
            'unallocated'    => $result->unallocatedRemainder,
        ]);

        return $botOrder;
    }
}
