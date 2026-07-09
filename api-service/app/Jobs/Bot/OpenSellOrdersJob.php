<?php

namespace App\Jobs\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Services\Bot\BotOrderStatusService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\SettlementService;
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
        SettlementService $settlement,
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
            $this->markFailed($execution, 'No active BotSignal for currency on sell-open', $exchange, $settlement);
            return;
        }

        $sellTargets = is_array($signal->sell_targets) ? $signal->sell_targets : [];
        $p2pMinUsdt  = (string) $signal->effective_p2p_min_order_value;
        $avgBuyPrice = (string) $execution->avg_buy_price;

        if (empty($sellTargets) || bccomp((string) $execution->filled_amount, '0', self::SCALE) <= 0) {
            $this->markFailed($execution, 'Missing sell_targets or filled_amount on sell-open', $exchange, $settlement);
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
            $this->markFailed($execution, 'Collapse produced no viable targets', $exchange, $settlement);
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
                $settlement,
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

        foreach ($result['final_targets'] as $target) {
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
                $this->markFailed(
                    $execution,
                    sprintf(
                        'coinex.sell.place_failed market=%s code=%s msg=%s',
                        $market,
                        $res->errorCode ?? 'n/a',
                        $res->errorMessage ?? 'no error message',
                    ),
                    $exchange,
                    $settlement,
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
     * Two cases:
     *   - filled_amount == 0  → no coin on hand. Just release the locked USDT
     *     back to wallet.balance (mirror of BuyExecutionJob::failed).
     *   - filled_amount  > 0  → coin is already sitting on the omnibus
     *     account. Market-sell it via the exchange driver and route the
     *     proceeds through SettlementService::settleFallbackLiquidation so
     *     the user's bot_wallet gets the realized USDT (typically with a
     *     small negative pnl) and a regular bot_trade_settlements row +
     *     transactions are produced. The locked principal is released by
     *     settleFallbackLiquidation's normal settleFill flow (it subtracts
     *     cost_basis from locked_balance).
     *
     * If the fallback market-sell itself fails (exchange unreachable / no
     * price), we still mark the execution FAILED but log a critical alert —
     * the coin is truly stranded and needs operator attention.
     */
    private function markFailed(
        BotBuyExecution $execution,
        string $reason,
        ExchangeContract $exchange,
        SettlementService $settlement,
    ): void {
        $filled = (string) $execution->filled_amount;
        $hasCoin = bccomp($filled, '0', self::SCALE) > 0;

        if (! $hasCoin) {
            // Release the locked USDT principal back to the user's balance.
            $this->releaseLockedPrincipal($execution);
            $execution->update([
                'status'         => BotBuyExecution::STATUS_FAILED,
                'failure_reason' => mb_substr($reason, 0, 250),
            ]);
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

        // Fallback liquidation path.
        $currency = $execution->currency ?? Currency::find($execution->currency_id);
        $market   = strtoupper((string) ($currency?->symbol ?? '')).'USDT';

        $disposeRes = $exchange->placeMarketSell($market, $filled);

        if ($disposeRes->exchangeOrderId === null || $disposeRes->filledAmount === null) {
            // Disposal failed — coin really stranded. Keep locked_balance as-is
            // for visibility; operator must reconcile manually.
            $execution->update([
                'status'         => BotBuyExecution::STATUS_FAILED,
                'failure_reason' => mb_substr(
                    $reason.' | dispose_failed code='.($disposeRes->errorCode ?? 'n/a')
                          .' msg='.($disposeRes->errorMessage ?? 'n/a'),
                    0,
                    250,
                ),
            ]);
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

        $settlement->settleFallbackLiquidation(
            execution:          $execution,
            filledAmount:       (string) $disposeRes->filledAmount,
            fillPrice:          (string) ($disposeRes->avgPrice ?? '0'),
            sellRefExchangeFee: (string) ($disposeRes->exchangeFee ?? '0'),
            exchangeOrderId:    $disposeRes->exchangeOrderId,
        );

        $execution->update([
            'status'         => BotBuyExecution::STATUS_FAILED,
            'failure_reason' => mb_substr($reason.' | auto-liquidated', 0, 250),
        ]);

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
     * Release this execution's allocated USDT from locked_balance back to the
     * spendable balance. Mirrors {@see BuyExecutionJob::failed()}. Used only
     * when there is no coin on hand to dispose of.
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
            $newBalance = bcadd((string) $wallet->balance, $allocated, self::SCALE);
            $wallet->update([
                'balance'        => $newBalance,
                'locked_balance' => $newLocked,
            ]);
        });
    }
}
