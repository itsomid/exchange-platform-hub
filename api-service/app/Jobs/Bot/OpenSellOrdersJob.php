<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Services\Bot\BotOrderDescriptionService;
use App\Services\Bot\BotOrderStatusService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\TargetCollapseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * For a successfully-bought bot_buy_execution, splits the filled amount into
 * the configured sell targets, applies D14 smart collapse, creates one
 * bot_sell_orders row per final target and immediately places a matching
 * LIMIT SELL on the reference exchange (CoinEx). The order id returned by
 * CoinEx is persisted in `exchange_order_id` so the polling sync command can
 * detect fills.
 */
class OpenSellOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SCALE = 8;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $executionId) {}

    public function handle(
        TargetCollapseService $collapser,
        ExchangeContract $exchange,
    ): void
    {
        $execution = BotBuyExecution::with('currency')->find($this->executionId);
        if (! $execution) {
            return;
        }
        if ($execution->status !== BotBuyExecution::STATUS_BOUGHT) {
            return;
        }
        // Idempotency: never re-fan-out an execution that already has sells.
        if (BotSellOrder::where('bot_buy_execution_id', $execution->id)->exists()) {
            return;
        }

        Log::channel('smart-bot')->info('bot.sell.open.start', [
            'execution_id'  => $execution->id,
            'currency'      => $execution->currency?->symbol,
            'filled_amount' => $execution->filled_amount,
            'avg_buy_price' => $execution->avg_buy_price,
            'access_id'     => config('exchanges.coinex.access_id'),
            'host'          => gethostname() ?: null,
            'pid'           => getmypid() ?: null,
            'queue'         => $this->job?->getQueue(),
            'attempt'       => $this->attempts(),
        ]);

        $signal = BotSignal::where('currency_id', $execution->currency_id)->first();
        if (! $signal) {
            $this->markFailed($execution, 'No active BotSignal for currency on sell-open', $exchange);
            return;
        }

        $sellTargets = is_array($signal->sell_targets) ? $signal->sell_targets : [];
        $p2pMinUsdt  = (string) $signal->effective_p2p_min_order_value;
        $avgBuyPrice = (string) $execution->avg_buy_price;

        if (empty($sellTargets) || bccomp((string) $execution->filled_amount, '0', self::SCALE) <= 0) {
            $this->markFailed($execution, 'Missing sell_targets or filled_amount on sell-open', $exchange);
            return;
        }

        // Convert USDT p2p_min into base-coin units once at the boundary so the
        // pure-base-amount collapser & single-target check operate consistently.
        $p2pMinBase = bccomp($avgBuyPrice, '0', self::SCALE) > 0
            ? bcdiv($p2pMinUsdt, $avgBuyPrice, self::SCALE)
            : '0';

        $result = $collapser->collapse(
            (string) $execution->filled_amount,
            $sellTargets,
            $p2pMinBase,
        );

        if (empty($result['final_targets'])) {
            $this->markFailed($execution, 'Collapse produced no viable targets', $exchange);
            return;
        }

        if (
            count($result['final_targets']) === 1 &&
            bccomp($result['final_targets'][0]['amount'], $p2pMinBase, self::SCALE) < 0
        ) {
            $singleAmount = $result['final_targets'][0]['amount'];
            $singleUsdt   = bcmul($singleAmount, $avgBuyPrice, self::SCALE);
            $this->markFailed(
                $execution,
                sprintf(
                    'Single target value %s USDT (amount=%s × price=%s) below p2p_min %s USDT',
                    $singleUsdt, $singleAmount, $avgBuyPrice, $p2pMinUsdt,
                ),
                $exchange,
            );
            return;
        }

        // Persist collapse metadata up front (does not depend on exchange placement).
        DB::transaction(function () use ($execution, $result) {
            $reasonSuffix = $result['collapsed'] ? ($result['note'] ?? null) : null;
            $execution->update([
                'original_sell_orders_count'  => $result['original_count'],
                'effective_sell_orders_count' => $result['effective_count'],
                'failure_reason'              => $reasonSuffix
                    ? trim(((string) $execution->failure_reason) . ' | ' . $reasonSuffix, ' |')
                    : $execution->failure_reason,
            ]);
        });

        $market      = strtoupper((string) $execution->currency->symbol).'USDT';
        $defaultMode = $signal->sell_mode ?? 'percent';
        $placedIds   = [];

        foreach ($result['final_targets'] as $index => $target) {
            $type     = $target['type'] ?? $defaultMode;
            $trigger  = (string) $target['trigger'];
            $price    = $this->resolveAbsolutePrice($type, $trigger, (string) $execution->avg_buy_price);
            $amount   = (string) $target['amount'];

            $res = $exchange->placeLimitSell($market, $amount, $price);
            if ($res->exchangeOrderId === null) {
                // Best-effort rollback of earlier placements before failing the execution.
                foreach ($placedIds as $earlierId) {
                    try { $exchange->cancelOrder($market, $earlierId); }
                    catch (\Throwable $e) {
                        Log::channel('smart-bot')->warning('bot.sell.open.rollback_failed', [
                            'execution_id' => $execution->id,
                            'order_id'     => $earlierId,
                            'error'        => $e->getMessage(),
                            'access_id'    => config('exchanges.coinex.access_id'),
                            'host'         => gethostname() ?: null,
                            'pid'          => getmypid() ?: null,
                        ]);
                    }
                }
                // Record the tiers we never managed to place (this one + any
                // remaining) as CANCELED rows with their real target values, so
                // the full sell plan stays visible and untouched — no synthetic
                // 100% liquidation row is invented.
                $this->persistCanceledTargets(
                    $execution,
                    array_slice($result['final_targets'], $index),
                    $defaultMode,
                );
                $this->markFailed(
                    $execution,
                    sprintf(
                        'coinex.sell.place_failed market=%s code=%s msg=%s',
                        $market,
                        $res->errorCode ?? 'n/a',
                        $res->errorMessage ?? 'no error message',
                    ),
                    $exchange,
                );
                return;
            }

            $placedIds[] = $res->exchangeOrderId;

            BotSellOrder::create([
                'bot_buy_execution_id' => $execution->id,
                'exchange_order_id'    => $res->exchangeOrderId,
                'target_type'          => $type,
                'target_value'         => $trigger,
                'share_percent'        => $target['share'],
                'amount_to_sell'       => $amount,
                'status'               => BotSellOrder::STATUS_OPEN,
            ]);
        }

        Log::channel('smart-bot')->info('bot.sell.opened', [
            'execution_id'    => $execution->id,
            'market'          => $market,
            'original_count'  => $result['original_count'],
            'effective_count' => $result['effective_count'],
            'collapsed'       => $result['collapsed'],
            'exchange_orders' => $placedIds,
            'access_id'       => config('exchanges.coinex.access_id'),
            'host'            => gethostname() ?: null,
            'pid'             => getmypid() ?: null,
        ]);
    }

    private function resolveAbsolutePrice(string $type, string $trigger, string $avgBuyPrice): string
    {
        if ($type === 'price') {
            return $trigger;
        }
        // percent: avg_buy_price * (1 + trigger/100)
        $factor = bcadd('1', bcdiv($trigger, '100', 10), 10);
        return bcmul($avgBuyPrice, $factor, self::SCALE);
    }

    /**
     * Mark a buy-execution FAILED and unwind side-effects.
     *
     * The failure here is on the reference-exchange side (a tier sell couldn't
     * be placed), NOT a decision by the user — so the user must be made whole:
     * their full locked principal is returned and NO fee or PnL is passed on.
     * No bot_trade_settlements row is created.
     *
     *   - filled_amount == 0  → no coin on hand. Just release the locked USDT
     *     principal (mirror of BuyExecutionJob::releaseAndFail).
     *   - filled_amount  > 0  → coin is sitting on the omnibus account.
     *     Market-sell it so it isn't stranded (the platform absorbs the
     *     realized proceeds/loss) and release the user's full locked principal.
     *
     * If the fallback market-sell itself fails (exchange unreachable / no
     * price), we still refund the user and mark the execution FAILED, but log
     * a critical alert — the coin is stranded and needs operator attention.
     */
    private function markFailed(
        BotBuyExecution $execution,
        string $reason,
        ExchangeContract $exchange,
    ): void {
        $filled = (string) $execution->filled_amount;
        $hasCoin = bccomp($filled, '0', self::SCALE) > 0;

        // Any tier sells that were already placed on the exchange got rolled
        // back there, but their DB rows are still OPEN. Since this execution
        // is failing, those tiers will never fill — mark them CANCELED so the
        // DB reflects reality.
        $this->cancelOpenSellOrders($execution);

        // Return the user's full locked principal regardless of the coin
        // disposal outcome: the failure was not their doing.
        $this->releaseLockedPrincipal($execution);

        if (! $hasCoin) {
            $execution->update([
                'status'         => BotBuyExecution::STATUS_FAILED,
                'failure_reason' => mb_substr($reason, 0, 250),
            ]);
            $this->recordOrderDescription($execution, $reason);
            Log::channel('smart-bot')->warning('bot.sell.open.failed', [
                'execution_id' => $execution->id,
                'reason'       => $reason,
                'stranded'     => false,
                'access_id'    => config('exchanges.coinex.access_id'),
                'host'         => gethostname() ?: null,
                'pid'          => getmypid() ?: null,
            ]);
            $this->finalizeParentOrder($execution);
            return;
        }

        // Dispose the bought coin on the reference exchange so it isn't left
        // stranded on the omnibus account. This is a platform-side operation:
        // the proceeds/loss are NOT settled against the user (they were already
        // refunded their full principal above).
        $currency = $execution->currency ?? Currency::find($execution->currency_id);
        $market   = strtoupper((string) ($currency?->symbol ?? '')).'USDT';

        $disposeRes = $exchange->placeMarketSell($market, $filled);

        if ($disposeRes->exchangeOrderId === null || $disposeRes->filledAmount === null) {
            // Disposal failed — coin really stranded; operator must reconcile.
            $strandedReason = $reason.' | dispose_failed code='.($disposeRes->errorCode ?? 'n/a')
                          .' msg='.($disposeRes->errorMessage ?? 'n/a');
            $execution->update([
                'status'         => BotBuyExecution::STATUS_FAILED,
                'failure_reason' => mb_substr($strandedReason, 0, 250),
            ]);
            $this->recordOrderDescription($execution, $strandedReason);
            Log::channel('smart-bot')->critical('bot.sell.open.stranded', [
                'execution_id'  => $execution->id,
                'currency_id'   => $execution->currency_id,
                'market'        => $market,
                'filled_amount' => $filled,
                'reason'        => $reason,
                'dispose_error' => $disposeRes->errorMessage,
                'dispose_code'  => $disposeRes->errorCode,
                'access_id'     => config('exchanges.coinex.access_id'),
                'host'          => gethostname() ?: null,
                'pid'           => getmypid() ?: null,
            ]);
            $this->finalizeParentOrder($execution);
            return;
        }

        $execution->update([
            'status'         => BotBuyExecution::STATUS_FAILED,
            'failure_reason' => mb_substr($reason.' | auto-liquidated', 0, 250),
        ]);

        $this->recordOrderDescription(
            $execution,
            $reason.' | auto-liquidated | liquidation_exchange_order_id='.$disposeRes->exchangeOrderId,
        );

        Log::channel('smart-bot')->warning('bot.sell.open.failed', [
            'execution_id'    => $execution->id,
            'reason'          => $reason,
            'stranded'        => true,
            'dispose_amount'  => $disposeRes->filledAmount,
            'dispose_price'   => $disposeRes->avgPrice,
            'dispose_orderid' => $disposeRes->exchangeOrderId,
            'access_id'       => config('exchanges.coinex.access_id'),
            'host'            => gethostname() ?: null,
            'pid'             => getmypid() ?: null,
        ]);
        $this->finalizeParentOrder($execution);
    }

    /**
     * A sell-open failure leaves this execution FAILED. If that means the whole
     * order now has no bought signal left, settle the order to FAILED instead
     * of leaving it stuck at PENDING.
     */
    private function finalizeParentOrder(BotBuyExecution $execution): void
    {
        app(BotOrderStatusService::class)->finalizeIfAllFailed((int) $execution->bot_order_id);
    }

    /**
     * Mark this execution's still-OPEN tier sells as CANCELED. They were
     * rolled back on the exchange when placement of a later tier failed, so
     * leaving them OPEN in the DB is stale.
     */
    private function cancelOpenSellOrders(BotBuyExecution $execution): void
    {
        BotSellOrder::where('bot_buy_execution_id', $execution->id)
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->update([
                'status'    => BotSellOrder::STATUS_CANCELED,
                'filled_at' => null,
            ]);
    }

    /**
     * Append a human-readable cancellation/failure note (including the CoinEx
     * error and any liquidation exchange order id) to the parent order's
     * `description`. Appended (not overwritten) so a multi-coin order keeps a
     * note per affected execution.
     */
    private function recordOrderDescription(BotBuyExecution $execution, string $note): void
    {
        app(BotOrderDescriptionService::class)->appendSystemNote($execution, $note);
    }

    /**
     * Persist tier targets we never managed to place as CANCELED sell orders,
     * preserving their real target_type / target_value / share / amount. Used
     * so a failed sell-open leaves the full, untouched tier plan visible
     * instead of inventing a synthetic 100% liquidation row.
     *
     * @param array<int,array<string,mixed>> $targets
     */
    private function persistCanceledTargets(BotBuyExecution $execution, array $targets, string $defaultMode): void
    {
        foreach ($targets as $target) {
            BotSellOrder::create([
                'bot_buy_execution_id' => $execution->id,
                'exchange_order_id'    => null,
                'target_type'          => $target['type'] ?? $defaultMode,
                'target_value'         => (string) $target['trigger'],
                'share_percent'        => $target['share'],
                'amount_to_sell'       => (string) $target['amount'],
                'status'               => BotSellOrder::STATUS_CANCELED,
            ]);
        }
    }

    /**
     * Release this execution's allocated USDT from locked_balance. Mirrors
     * {@see BuyExecutionJob::releaseAndFail()}: the principal was never taken
     * out of `balance` at buy time (only `locked_balance` was incremented), so
     * we only unlock it here — adding it back to `balance` would double-count
     * and inflate the wallet.
     */
    private function releaseLockedPrincipal(BotBuyExecution $execution): void
    {
        DB::transaction(function () use ($execution) {
            $userId = $execution->botOrder?->user_id;
            if (! $userId) {
                return;
            }
            $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (! $wallet) {
                return;
            }
            $allocated = (string) $execution->allocated_usdt;
            if (bccomp($allocated, '0', self::SCALE) <= 0) {
                return;
            }
            $newLocked = bcsub((string) $wallet->locked_balance, $allocated, self::SCALE);
            if (bccomp($newLocked, '0', self::SCALE) < 0) {
                $newLocked = '0';
            }
            $wallet->update([
                'locked_balance' => $newLocked,
            ]);
        });
    }
}
